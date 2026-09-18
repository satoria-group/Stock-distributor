<?php

namespace Tests\Feature;

use App\Models\Distributor;
use App\Models\DistributorItem;
use App\Models\NetsuiteItem;
use App\Models\StockEmailLog;
use App\Models\StockEntry;
use App\Services\ImapService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ProcessStockEmailsCommandTest extends TestCase
{
    use DatabaseTransactions;

    private function createSampleExcel(string $distCode = 'DIST_AUTO_TEST', string $date = '14/09/2026', string $itemName = 'Cefotaxime 1g'): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template');

        $headers = ['Tanggal', 'ID DISTRIBUTOR', 'Distributor Item Name', 'Quantity', 'Satuan', 'ED', 'Batch No'];
        foreach ($headers as $colIdx => $h) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1);
            $sheet->setCellValue("{$colLetter}1", $h);
        }

        $sheet->setCellValue('A2', $date);
        $sheet->setCellValue('B2', $distCode);
        $sheet->setCellValue('C2', $itemName);
        $sheet->setCellValue('D2', 250);
        $sheet->setCellValue('E2', 'BTL');
        $sheet->setCellValue('F2', '20/12/2027');
        $sheet->setCellValue('G2', 'BATCH-AUTO-999');

        $writer = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'test_cmd_').'.xlsx';
        $writer->save($tempFile);
        $content = file_get_contents($tempFile);
        @unlink($tempFile);

        return $content;
    }

    public function test_command_warns_when_imap_not_configured(): void
    {
        $mock = Mockery::mock(ImapService::class);
        $mock->shouldReceive('isConfigured')->andReturn(false);
        $this->app->instance(ImapService::class, $mock);

        $this->artisan('stock:process-emails')
            ->expectsOutputToContain('Koneksi mail server IMAP belum dikonfigurasi')
            ->assertSuccessful();
    }

    public function test_command_processes_valid_email_and_imports_stock(): void
    {
        $dist = Distributor::create([
            'distributor_code' => 'DIST_CMD_OK',
            'name' => 'Distributor Command OK',
            'sender_email' => 'logistik@dist-ok.co.id',
            'is_active' => true,
        ]);

        $ns = NetsuiteItem::create([
            'netsuite_id' => 'NS-CMD-OK',
            'netsuite_name' => 'Paracetamol 500mg',
        ]);

        $item = DistributorItem::create([
            'distributor_id' => $dist->id,
            'item_name' => 'Paracetamol 500mg Satoria',
            'satuan' => 'BTL',
            'netsuite_item_id' => $ns->id,
        ]);

        $excelBytes = $this->createSampleExcel('DIST_CMD_OK', '14/09/2026', 'Paracetamol 500mg Satoria');

        $mock = Mockery::mock(ImapService::class);
        $mock->shouldReceive('isConfigured')->andReturn(true);
        $mock->shouldReceive('getUnreadMessages')
            ->with(10)
            ->once()
            ->andReturn([
                [
                    'uid' => '9901',
                    'message_id' => '<msg-9901@dist-ok.co.id>',
                    'from_name' => 'Logistik Dist OK',
                    'from_email' => 'logistik@dist-ok.co.id',
                    'subject' => 'Satoria Daily Stock - 14 September 2026',
                    'date' => now(),
                    'is_daily_stock' => true,
                    'has_attachments' => true,
                ],
            ]);
        $mock->shouldReceive('getExcelAttachment')
            ->with('9901')
            ->once()
            ->andReturn([
                'filename' => 'Daily_Stock_DIST_CMD_OK.xlsx',
                'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'content' => $excelBytes,
                'size' => strlen($excelBytes),
            ]);
        $mock->shouldReceive('markAsRead')
            ->with('9901')
            ->once()
            ->andReturn(true);

        $this->app->instance(ImapService::class, $mock);

        $this->artisan('stock:process-emails')
            ->expectsOutputToContain('BERHASIL: Diimpor 1 baris untuk DIST_CMD_OK')
            ->assertSuccessful();

        // Verify stock entry created
        $entry = StockEntry::where('distributor_id', $dist->id)
            ->where('tanggal', '2026-09-14')
            ->where('distributor_item_id', $item->id)
            ->first();

        $this->assertNotNull($entry);
        $this->assertEquals(250.0, (float) $entry->quantity);
        $this->assertEquals('BTL', $entry->satuan);
        $this->assertEquals('BATCH-AUTO-999', $entry->batch_no);

        // Verify audit log
        $log = StockEmailLog::where('email_uid', '9901')->first();
        $this->assertNotNull($log);
        $this->assertEquals('success', $log->status);
        $this->assertEquals(1, $log->imported_rows);
        $this->assertEquals('DIST_CMD_OK', $log->distributor_code);
    }

    public function test_command_rejects_unauthorized_sender_whitelist(): void
    {
        $dist = Distributor::create([
            'distributor_code' => 'DIST_SECURE',
            'name' => 'Distributor Secure',
            'sender_email' => 'authorized@partner.com',
            'is_active' => true,
        ]);

        DistributorItem::create([
            'distributor_id' => $dist->id,
            'item_name' => 'Amoxicillin 500mg',
            'satuan' => 'BTL',
        ]);

        $excelBytes = $this->createSampleExcel('DIST_SECURE', '15/09/2026', 'Amoxicillin 500mg');

        $mock = Mockery::mock(ImapService::class);
        $mock->shouldReceive('isConfigured')->andReturn(true);
        $mock->shouldReceive('getUnreadMessages')->andReturn([
            [
                'uid' => '9902',
                'message_id' => '<attacker@evil.com>',
                'from_name' => 'Untrusted Sender',
                'from_email' => 'unauthorized@evil.com',
                'subject' => 'Satoria Daily Stock Spoofed',
                'date' => now(),
                'is_daily_stock' => true,
                'has_attachments' => true,
            ],
        ]);
        $mock->shouldReceive('getExcelAttachment')->with('9902')->andReturn([
            'filename' => 'fake.xlsx',
            'content' => $excelBytes,
        ]);
        $mock->shouldReceive('markAsRead')->with('9902')->once()->andReturn(true);

        $this->app->instance(ImapService::class, $mock);

        $this->artisan('stock:process-emails')
            ->expectsOutputToContain('GAGAL (unauthorized_sender)')
            ->assertSuccessful();

        // Ensure NO stock entry created
        $this->assertDatabaseMissing('stock_entries', [
            'distributor_id' => $dist->id,
            'tanggal' => '2026-09-15',
        ]);

        // Audit log created with unauthorized_sender
        $log = StockEmailLog::where('email_uid', '9902')->first();
        $this->assertNotNull($log);
        $this->assertEquals('unauthorized_sender', $log->status);
    }

    public function test_command_prevents_duplicate_processing_idempotency(): void
    {
        StockEmailLog::create([
            'email_uid' => '9903',
            'message_id' => '<already-done@dist.com>',
            'from_email' => 'dist@test.com',
            'subject' => 'Satoria Daily Stock Duplicate',
            'status' => 'success',
            'imported_rows' => 10,
        ]);

        $mock = Mockery::mock(ImapService::class);
        $mock->shouldReceive('isConfigured')->andReturn(true);
        $mock->shouldReceive('getUnreadMessages')->andReturn([
            [
                'uid' => '9903',
                'message_id' => '<already-done@dist.com>',
                'from_name' => 'Dist',
                'from_email' => 'dist@test.com',
                'subject' => 'Satoria Daily Stock Duplicate',
                'date' => now(),
                'is_daily_stock' => true,
                'has_attachments' => true,
            ],
        ]);

        $this->app->instance(ImapService::class, $mock);

        $this->artisan('stock:process-emails')
            ->expectsOutputToContain('sudah pernah sukses diproses')
            ->assertSuccessful();
    }

    public function test_command_rejects_corrupt_template_cleanly(): void
    {
        // Spreadsheet without required columns
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template');
        $sheet->setCellValue('A1', 'Kolom_Salah_1');
        $sheet->setCellValue('B1', 'Kolom_Salah_2');
        $sheet->setCellValue('A2', 'Data 1');
        $sheet->setCellValue('B2', 'Data 2');

        $writer = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'test_corrupt_').'.xlsx';
        $writer->save($tempFile);
        $corruptBytes = file_get_contents($tempFile);
        @unlink($tempFile);

        $mock = Mockery::mock(ImapService::class);
        $mock->shouldReceive('isConfigured')->andReturn(true);
        $mock->shouldReceive('getUnreadMessages')->andReturn([
            [
                'uid' => '9904',
                'message_id' => '<corrupt@dist.com>',
                'from_name' => 'Dist',
                'from_email' => 'dist@test.com',
                'subject' => 'Satoria Daily Stock Corrupt',
                'date' => now(),
                'is_daily_stock' => true,
                'has_attachments' => true,
            ],
        ]);
        $mock->shouldReceive('getExcelAttachment')->with('9904')->andReturn([
            'filename' => 'corrupt.xlsx',
            'content' => $corruptBytes,
        ]);
        $mock->shouldReceive('markAsRead')->with('9904')->once()->andReturn(true);

        $this->app->instance(ImapService::class, $mock);

        $this->artisan('stock:process-emails')
            ->expectsOutputToContain('GAGAL (invalid_template)')
            ->assertSuccessful();

        $log = StockEmailLog::where('email_uid', '9904')->first();
        $this->assertNotNull($log);
        $this->assertEquals('invalid_template', $log->status);
    }

    public function test_command_rejects_email_when_distributor_has_no_whitelist_configured(): void
    {
        // Distributor without sender_email configured (null/empty)
        $dist = Distributor::create([
            'distributor_code' => 'DIST_NO_WL_' . uniqid(),
            'name' => 'Distributor Tanpa Whitelist',
            'sender_email' => null,
            'is_active' => true,
        ]);

        $excelBytes = $this->createSampleExcel($dist->distributor_code, '16/09/2026', 'Paracetamol 500mg');

        $mock = Mockery::mock(ImapService::class);
        $mock->shouldReceive('isConfigured')->andReturn(true);
        $mock->shouldReceive('getUnreadMessages')->andReturn([
            [
                'uid' => '9905',
                'message_id' => '<unconfigured@partner.com>',
                'from_name' => 'Partner Staff',
                'from_email' => 'staff@partner.com',
                'subject' => 'Satoria Daily Stock - Tanpa Whitelist',
                'date' => now(),
                'is_daily_stock' => true,
                'has_attachments' => true,
            ],
        ]);
        $mock->shouldReceive('getExcelAttachment')->with('9905')->andReturn([
            'filename' => 'stock_no_wl.xlsx',
            'content' => $excelBytes,
        ]);
        $mock->shouldReceive('markAsRead')->with('9905')->once()->andReturn(true);

        $this->app->instance(ImapService::class, $mock);

        $this->artisan('stock:process-emails')
            ->expectsOutputToContain('GAGAL (unauthorized_sender)')
            ->assertSuccessful();

        $log = StockEmailLog::where('email_uid', '9905')->first();
        $this->assertNotNull($log);
        $this->assertEquals('unauthorized_sender', $log->status);
        $this->assertStringContainsString('belum mendaftarkan email whitelist', $log->error_message);
    }

    public function test_command_rejects_inactive_distributor(): void
    {
        $dist = Distributor::create([
            'distributor_code' => 'DIST_INACTIVE_' . uniqid(),
            'name' => 'Distributor Non-Aktif',
            'sender_email' => 'logistik@inactive.co.id',
            'is_active' => false,
        ]);

        $excelBytes = $this->createSampleExcel($dist->distributor_code, '16/09/2026', 'Cefotaxime 1g');

        $mock = Mockery::mock(ImapService::class);
        $mock->shouldReceive('isConfigured')->andReturn(true);
        $mock->shouldReceive('getUnreadMessages')->andReturn([
            [
                'uid' => '9906',
                'message_id' => '<msg@inactive.co.id>',
                'from_name' => 'Logistik Inactive',
                'from_email' => 'logistik@inactive.co.id',
                'subject' => 'Satoria Daily Stock - Inactive',
                'date' => now(),
                'is_daily_stock' => true,
                'has_attachments' => true,
            ],
        ]);
        $mock->shouldReceive('getExcelAttachment')->with('9906')->andReturn([
            'filename' => 'inactive.xlsx',
            'content' => $excelBytes,
        ]);
        $mock->shouldReceive('markAsRead')->with('9906')->once()->andReturn(true);

        $this->app->instance(ImapService::class, $mock);

        $this->artisan('stock:process-emails')
            ->expectsOutputToContain('GAGAL (inactive_distributor)')
            ->assertSuccessful();

        $log = StockEmailLog::where('email_uid', '9906')->first();
        $this->assertNotNull($log);
        $this->assertEquals('inactive_distributor', $log->status);
        $this->assertStringContainsString('NON-AKTIF', $log->error_message);
    }

    public function test_command_records_unmapped_item_details_and_names(): void
    {
        $dist = Distributor::create([
            'distributor_code' => 'DIST_UNMAPPED_' . uniqid(),
            'name' => 'Distributor Partial Test',
            'sender_email' => 'sender@partial.com',
            'is_active' => true,
        ]);

        $nsKnown = NetsuiteItem::create([
            'netsuite_id' => 'NS-AMOX-500',
            'netsuite_name' => 'Amoxicillin 500mg',
        ]);

        // Create 1 known item, leave 1 unknown item
        $knownItem = DistributorItem::create([
            'distributor_id' => $dist->id,
            'item_name' => 'Amoxicillin 500mg',
            'satuan' => 'BTL',
            'netsuite_item_id' => $nsKnown->id,
        ]);

        // Create spreadsheet with 2 items: 1 mapped, 1 unmapped
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template');
        $headers = ['Tanggal', 'ID DISTRIBUTOR', 'Distributor Item Name', 'Quantity', 'Satuan', 'ED', 'Batch No'];
        foreach ($headers as $colIdx => $h) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1);
            $sheet->setCellValue("{$colLetter}1", $h);
        }

        // Row 1: Known
        $sheet->setCellValue('A2', '16/09/2026');
        $sheet->setCellValue('B2', $dist->distributor_code);
        $sheet->setCellValue('C2', 'Amoxicillin 500mg');
        $sheet->setCellValue('D2', 100);
        $sheet->setCellValue('E2', 'BTL');
        $sheet->setCellValue('F2', '20/12/2027');
        $sheet->setCellValue('G2', 'BATCH-KNOWN-01');

        // Row 2: Unknown / Unmapped item
        $sheet->setCellValue('A3', '16/09/2026');
        $sheet->setCellValue('B3', $dist->distributor_code);
        $sheet->setCellValue('C3', 'Satoria IV Infusion D5 500ml Unmapped');
        $sheet->setCellValue('D3', 50);
        $sheet->setCellValue('E3', 'KRT');
        $sheet->setCellValue('F3', '15/05/2028');
        $sheet->setCellValue('G3', 'BATCH-UNMAPPED-99');

        $writer = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'test_part_').'.xlsx';
        $writer->save($tempFile);
        $excelBytes = file_get_contents($tempFile);
        @unlink($tempFile);

        $mock = Mockery::mock(ImapService::class);
        $mock->shouldReceive('isConfigured')->andReturn(true);
        $mock->shouldReceive('getUnreadMessages')->andReturn([
            [
                'uid' => '9907',
                'message_id' => '<partial@test.com>',
                'from_name' => 'Partial Sender',
                'from_email' => 'sender@partial.com',
                'subject' => 'Satoria Daily Stock - Partial Unmapped',
                'date' => now(),
                'is_daily_stock' => true,
                'has_attachments' => true,
            ],
        ]);
        $mock->shouldReceive('getExcelAttachment')->with('9907')->andReturn([
            'filename' => 'partial.xlsx',
            'content' => $excelBytes,
        ]);
        $mock->shouldReceive('markAsRead')->with('9907')->once()->andReturn(true);

        $this->app->instance(ImapService::class, $mock);

        $this->artisan('stock:process-emails')
            ->expectsOutputToContain('BERHASIL: Diimpor 1 baris')
            ->expectsOutputToContain('Satoria IV Infusion D5 500ml Unmapped')
            ->assertSuccessful();

        $log = StockEmailLog::where('email_uid', '9907')->first();
        $this->assertNotNull($log);
        $this->assertEquals('partial_unmapped', $log->status);
        $this->assertEquals(1, $log->imported_rows);
        $this->assertEquals(1, $log->skipped_rows);
        $this->assertStringContainsString('Satoria IV Infusion D5 500ml Unmapped', $log->error_message);
        $this->assertContains('Satoria IV Infusion D5 500ml Unmapped', $log->details['unique_skipped_names'] ?? []);
    }

    public function test_command_rejects_duplicate_distributor_and_date_with_data_already_exists(): void
    {
        $code = 'DIST_DUP_' . uniqid();
        $dist = Distributor::create([
            'distributor_code' => $code,
            'name' => 'Distributor Duplikat Test',
            'sender_email' => 'sender@duplikat.com',
            'is_active' => true,
        ]);

        $item = DistributorItem::create([
            'distributor_id' => $dist->id,
            'item_name' => 'Cefotaxime 1g Satoria',
            'satuan' => 'BTL',
        ]);

        // Pre-create existing stock entry for 2026-09-16
        $existingEntry = StockEntry::create([
            'distributor_id' => $dist->id,
            'distributor_item_id' => $item->id,
            'tanggal' => '2026-09-16',
            'quantity' => 100,
            'satuan' => 'BTL',
            'expired_date' => '2027-12-20',
            'batch_no' => 'ORIGINAL-BATCH-001',
        ]);

        // Prepare incoming email spreadsheet for the SAME distributor and SAME date, but different qty/batch
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template');
        $headers = ['Tanggal', 'ID DISTRIBUTOR', 'Distributor Item Name', 'Quantity', 'Satuan', 'ED', 'Batch No'];
        foreach ($headers as $colIdx => $h) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1);
            $sheet->setCellValue("{$colLetter}1", $h);
        }
        $sheet->setCellValue('A2', '16/09/2026');
        $sheet->setCellValue('B2', $code);
        $sheet->setCellValue('C2', 'Cefotaxime 1g Satoria');
        $sheet->setCellValue('D2', 999);
        $sheet->setCellValue('E2', 'BTL');
        $sheet->setCellValue('F2', '20/12/2027');
        $sheet->setCellValue('G2', 'NEW-ATTEMPT-BATCH');

        $writer = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'test_dup_').'.xlsx';
        $writer->save($tempFile);
        $excelBytes = file_get_contents($tempFile);
        @unlink($tempFile);

        $mock = Mockery::mock(ImapService::class);
        $mock->shouldReceive('isConfigured')->andReturn(true);
        $mock->shouldReceive('getUnreadMessages')->andReturn([
            [
                'uid' => '9908',
                'message_id' => '<duplicate@duplikat.com>',
                'from_name' => 'Sender Duplikat',
                'from_email' => 'sender@duplikat.com',
                'subject' => 'Satoria Daily Stock - Duplicate Date Test',
                'date' => now(),
                'is_daily_stock' => true,
                'has_attachments' => true,
            ],
        ]);
        $mock->shouldReceive('getExcelAttachment')->with('9908')->andReturn([
            'filename' => 'duplicate.xlsx',
            'content' => $excelBytes,
        ]);
        $mock->shouldReceive('markAsRead')->with('9908')->once()->andReturn(true);

        $this->app->instance(ImapService::class, $mock);

        $this->artisan('stock:process-emails')
            ->expectsOutputToContain('DITOLAK (data_already_exists)')
            ->assertSuccessful();

        // Ensure original database entry remains UNTOUCHED
        $existingEntry->refresh();
        $this->assertEquals(100.0, (float) $existingEntry->quantity);
        $this->assertEquals('ORIGINAL-BATCH-001', $existingEntry->batch_no);

        // Audit log created with data_already_exists
        $log = StockEmailLog::where('email_uid', '9908')->first();
        $this->assertNotNull($log);
        $this->assertEquals('data_already_exists', $log->status);
        $this->assertEquals(0, $log->imported_rows);
        $this->assertStringContainsString('Data sudah ada', $log->error_message);
        $this->assertStringContainsString('Silakan upload manual', $log->error_message);
    }

    /**
     * Dua batch berbeda pada item yang sama harus tersimpan sebagai DUA baris
     * terpisah, masing-masing dengan ED-nya sendiri.
     *
     * Sebelumnya keduanya dilebur: kuantitas dijumlah, nomor batch disambung
     * koma, dan ED diambil yang paling awal — sehingga stok ber-ED 2028 ikut
     * ditandai mendekati kedaluwarsa hanya karena batch lain ber-ED 2027.
     */
    public function test_command_keeps_batches_separate_on_email_import(): void
    {
        $code = 'DIST_MRG_' . uniqid();
        $dist = Distributor::create([
            'distributor_code' => $code,
            'name' => 'Distributor Merge Test',
            'sender_email' => 'merge@distributor.com',
            'is_active' => true,
        ]);

        $ns = NetsuiteItem::create([
            'netsuite_id' => 'NS_MRG_' . uniqid(),
            'netsuite_name' => 'Produk Gabungan Test',
        ]);

        $item = DistributorItem::create([
            'distributor_id' => $dist->id,
            'item_name' => 'Produk Gabungan Test',
            'satuan' => 'BTL',
            'netsuite_item_id' => $ns->id,
        ]);

        // Excel with 2 rows for the SAME product, different batches and EDs
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template');
        $headers = ['Tanggal', 'ID DISTRIBUTOR', 'Distributor Item Name', 'Quantity', 'Satuan', 'ED', 'Batch No'];
        foreach ($headers as $colIdx => $h) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1);
            $sheet->setCellValue("{$colLetter}1", $h);
        }

        // Row 1: Batch 1 with 50 Qty, ED 2028-12-31
        $sheet->setCellValue('A2', '16/09/2026');
        $sheet->setCellValue('B2', $code);
        $sheet->setCellValue('C2', 'Produk Gabungan Test');
        $sheet->setCellValue('D2', 50);
        $sheet->setCellValue('E2', 'BTL');
        $sheet->setCellValue('F2', '31/12/2028');
        $sheet->setCellValue('G2', 'BATCH-001');

        // Row 2: Batch 2 with 30 Qty, ED 2027-06-30 (earlier)
        $sheet->setCellValue('A3', '16/09/2026');
        $sheet->setCellValue('B3', $code);
        $sheet->setCellValue('C3', 'Produk Gabungan Test');
        $sheet->setCellValue('D3', 30);
        $sheet->setCellValue('E3', 'BTL');
        $sheet->setCellValue('F3', '30/06/2027');
        $sheet->setCellValue('G3', 'BATCH-002');

        $writer = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'test_mrg_').'.xlsx';
        $writer->save($tempFile);
        $excelBytes = file_get_contents($tempFile);
        @unlink($tempFile);

        $mock = Mockery::mock(ImapService::class);
        $mock->shouldReceive('isConfigured')->andReturn(true);
        $mock->shouldReceive('getUnreadMessages')->andReturn([
            [
                'uid' => '9909',
                'message_id' => '<merge@test.com>',
                'from_name' => 'Merge Sender',
                'from_email' => 'merge@distributor.com',
                'subject' => 'Satoria Daily Stock - Multi Batch Test',
                'date' => now(),
                'is_daily_stock' => true,
                'has_attachments' => true,
            ],
        ]);
        $mock->shouldReceive('getExcelAttachment')->with('9909')->andReturn([
            'filename' => 'multi_batch.xlsx',
            'content' => $excelBytes,
        ]);
        $mock->shouldReceive('markAsRead')->with('9909')->once()->andReturn(true);

        $this->app->instance(ImapService::class, $mock);

        $this->artisan('stock:process-emails')
            ->expectsOutputToContain("BERHASIL: Diimpor 2 baris untuk {$code}")
            ->assertSuccessful();

        $entries = StockEntry::where('distributor_id', $dist->id)
            ->where('tanggal', '2026-09-16')
            ->where('distributor_item_id', $item->id)
            ->get()
            ->keyBy('batch_no');

        $this->assertCount(2, $entries, 'Dua batch berbeda seharusnya jadi dua baris, bukan dilebur jadi satu.');

        $this->assertEquals(50.0, (float) $entries['BATCH-001']->quantity);
        $this->assertEquals('2028-12-31', $entries['BATCH-001']->expired_date->toDateString());

        $this->assertEquals(30.0, (float) $entries['BATCH-002']->quantity);
        $this->assertEquals('2027-06-30', $entries['BATCH-002']->expired_date->toDateString());

        // Yang lama: satu baris qty 80, batch "BATCH-001, BATCH-002", ED 2027.
        $this->assertDatabaseMissing('stock_entries', [
            'distributor_item_id' => $item->id,
            'tanggal' => '2026-09-16',
            'batch_no' => 'BATCH-001, BATCH-002',
        ]);
    }

    /**
     * Batch No kosong menolak seluruh berkas — batch kini bagian dari identitas
     * baris, jadi tanpa itu dua baris berbeda tidak bisa dibedakan.
     */
    public function test_command_rejects_email_when_batch_number_is_empty(): void
    {
        [$code, $dist, $item] = $this->makeBatchScenarioFixtures('DIST_NOBATCH_', 'nobatch@distributor.com');

        $excelBytes = $this->buildBatchExcel($code, 'Produk Gabungan Test', [
            ['qty' => 50, 'ed' => '31/12/2028', 'batch' => 'BATCH-001'],
            ['qty' => 30, 'ed' => '30/06/2027', 'batch' => ''],   // <- kosong
        ]);

        $this->runCommandWithAttachment($excelBytes, '9911', 'nobatch@distributor.com');

        $this->assertSame(0, StockEntry::where('distributor_item_id', $item->id)->count(),
            'Berkas dengan batch kosong harus ditolak SELURUHNYA, tidak sebagian tersimpan.');

        $log = StockEmailLog::where('email_uid', '9911')->latest()->first();
        $this->assertSame('invalid_batch_data', $log->status);
        $this->assertStringContainsString('Batch No', $log->error_message);
    }

    /**
     * Nomor batch yang sama dengan ED berbeda hampir pasti salah input —
     * secara farmasi satu batch hanya punya satu tanggal kedaluwarsa.
     */
    public function test_command_rejects_email_when_same_batch_has_conflicting_ed(): void
    {
        [$code, $dist, $item] = $this->makeBatchScenarioFixtures('DIST_EDCONF_', 'edconf@distributor.com');

        $excelBytes = $this->buildBatchExcel($code, 'Produk Gabungan Test', [
            ['qty' => 50, 'ed' => '31/12/2028', 'batch' => 'BATCH-SAMA'],
            ['qty' => 30, 'ed' => '30/06/2027', 'batch' => 'BATCH-SAMA'],  // ED bentrok
        ]);

        $this->runCommandWithAttachment($excelBytes, '9912', 'edconf@distributor.com');

        $this->assertSame(0, StockEntry::where('distributor_item_id', $item->id)->count());

        $log = StockEmailLog::where('email_uid', '9912')->latest()->first();
        $this->assertSame('invalid_batch_data', $log->status);
        $this->assertStringContainsString('ED berbeda', $log->error_message);
    }

    /**
     * Baris dengan (item, batch) yang benar-benar identik tetap dijumlahkan —
     * itu memang satu tumpukan stok yang sama, bukan dua batch.
     */
    public function test_command_sums_rows_with_identical_item_and_batch(): void
    {
        [$code, $dist, $item] = $this->makeBatchScenarioFixtures('DIST_SAMEB_', 'sameb@distributor.com');

        $excelBytes = $this->buildBatchExcel($code, 'Produk Gabungan Test', [
            ['qty' => 500, 'ed' => '31/12/2028', 'batch' => 'BATCH-KEMBAR'],
            ['qty' => 300, 'ed' => '31/12/2028', 'batch' => 'batch-kembar '], // beda huruf/spasi
        ]);

        $this->runCommandWithAttachment($excelBytes, '9913', 'sameb@distributor.com');

        $entries = StockEntry::where('distributor_item_id', $item->id)->get();

        $this->assertCount(1, $entries, 'Batch yang sama (beda huruf besar-kecil) harus dianggap satu.');
        $this->assertEquals(800.0, (float) $entries->first()->quantity);
    }

    // ---------------------------------------------------------------
    // Helper untuk skenario batch
    // ---------------------------------------------------------------

    private function makeBatchScenarioFixtures(string $prefix, string $senderEmail): array
    {
        $code = $prefix.uniqid();
        $dist = Distributor::create([
            'distributor_code' => $code,
            'name' => 'Distributor Batch Test',
            // Harus PERSIS sama dengan pengirim yang dipakai di test, kalau
            // tidak importer menolaknya sebagai unauthorized_sender dan aturan
            // batch tidak pernah tereksekusi.
            'sender_email' => $senderEmail,
            'is_active' => true,
        ]);

        $ns = NetsuiteItem::create([
            'netsuite_id' => 'NS_'.uniqid(),
            'netsuite_name' => 'Produk Gabungan Test',
        ]);

        $item = DistributorItem::create([
            'distributor_id' => $dist->id,
            'item_name' => 'Produk Gabungan Test',
            'satuan' => 'BTL',
            'netsuite_item_id' => $ns->id,
        ]);

        return [$code, $dist, $item];
    }

    private function buildBatchExcel(string $code, string $itemName, array $rows): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template');

        foreach (['Tanggal', 'ID DISTRIBUTOR', 'Distributor Item Name', 'Quantity', 'Satuan', 'ED', 'Batch No'] as $i => $h) {
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1).'1', $h);
        }

        $r = 2;
        foreach ($rows as $row) {
            $sheet->setCellValue("A{$r}", '16/09/2026');
            $sheet->setCellValue("B{$r}", $code);
            $sheet->setCellValue("C{$r}", $itemName);
            $sheet->setCellValue("D{$r}", $row['qty']);
            $sheet->setCellValue("E{$r}", 'BTL');
            $sheet->setCellValue("F{$r}", $row['ed']);
            $sheet->setCellValueExplicit("G{$r}", $row['batch'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $r++;
        }

        $writer = new Xlsx($spreadsheet);
        $tempBase = tempnam(sys_get_temp_dir(), 'test_batch_');
        $tempFile = $tempBase.'.xlsx';
        $writer->save($tempFile);
        $bytes = file_get_contents($tempFile);
        @unlink($tempFile);
        @unlink($tempBase);

        return $bytes;
    }

    private function runCommandWithAttachment(string $excelBytes, string $uid, string $fromEmail): void
    {
        $mock = Mockery::mock(ImapService::class);
        $mock->shouldReceive('isConfigured')->andReturn(true);
        $mock->shouldReceive('getUnreadMessages')->andReturn([[
            'uid' => $uid,
            'message_id' => "<{$uid}@test.com>",
            'from_name' => 'Batch Sender',
            'from_email' => $fromEmail,
            'subject' => 'Satoria Daily Stock - Batch Test',
            'date' => now(),
            'is_daily_stock' => true,
            'has_attachments' => true,
        ]]);
        $mock->shouldReceive('getExcelAttachment')->with($uid)->andReturn([
            'filename' => 'batch.xlsx',
            'content' => $excelBytes,
        ]);
        $mock->shouldReceive('markAsRead')->andReturn(true);

        $this->app->instance(ImapService::class, $mock);

        $this->artisan('stock:process-emails')->assertSuccessful();
    }

    public function test_command_rejects_email_when_all_items_are_unmapped(): void
    {
        $code = 'DIST_ALL_UNM_' . uniqid();
        $dist = Distributor::create([
            'distributor_code' => $code,
            'name' => 'Distributor All Unmapped Test',
            'sender_email' => 'unmapped@distributor.com',
            'is_active' => true,
        ]);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template');
        $headers = ['Tanggal', 'ID DISTRIBUTOR', 'Distributor Item Name', 'Quantity', 'Satuan', 'ED', 'Batch No'];
        foreach ($headers as $colIdx => $h) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1);
            $sheet->setCellValue("{$colLetter}1", $h);
        }

        $sheet->setCellValue('A2', '16/09/2026');
        $sheet->setCellValue('B2', $code);
        $sheet->setCellValue('C2', 'PRODUK BARU TIDAK DIKENAL 1');
        $sheet->setCellValue('D2', 100);
        $sheet->setCellValue('E2', 'BTL');
        $sheet->setCellValue('F2', '31/12/2028');
        $sheet->setCellValue('G2', 'BATCH-NEW-01');

        $writer = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'test_all_unm_').'.xlsx';
        $writer->save($tempFile);
        $excelBytes = file_get_contents($tempFile);
        @unlink($tempFile);

        $mock = Mockery::mock(ImapService::class);
        $mock->shouldReceive('isConfigured')->andReturn(true);
        $mock->shouldReceive('getUnreadMessages')->andReturn([
            [
                'uid' => '9910',
                'message_id' => '<allunmapped@test.com>',
                'from_name' => 'Unmapped Sender',
                'from_email' => 'unmapped@distributor.com',
                'subject' => 'Satoria Daily Stock - All Unmapped Test',
                'date' => now(),
                'is_daily_stock' => true,
                'has_attachments' => true,
            ],
        ]);
        $mock->shouldReceive('getExcelAttachment')->with('9910')->andReturn([
            'filename' => 'all_unmapped.xlsx',
            'content' => $excelBytes,
        ]);
        $mock->shouldReceive('markAsRead')->with('9910')->once()->andReturn(true);

        $this->app->instance(ImapService::class, $mock);

        $this->artisan('stock:process-emails')
            ->expectsOutputToContain('DITOLAK (all_unmapped)')
            ->assertSuccessful();

        $log = StockEmailLog::where('email_uid', '9910')->first();
        $this->assertNotNull($log);
        $this->assertEquals('all_unmapped', $log->status);
        $this->assertEquals(0, $log->imported_rows);

        // Ensure 0 entries in stock_entries
        $count = StockEntry::where('distributor_id', $dist->id)->count();
        $this->assertEquals(0, $count);
    }
}
