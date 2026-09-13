<?php

namespace Tests\Feature;

use App\Models\Distributor;
use App\Models\DistributorItem;
use App\Models\User;
use App\Services\ImapService;
use Livewire\Livewire;
use Mockery;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class StockUploadFromEmailTest extends TestCase
{
    private function createSampleExcelContent(string $distCode = 'TEST_DIST_01'): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template');

        $headers = ['Tanggal', 'ID DISTRIBUTOR', 'Distributor Item Name', 'Quantity', 'Satuan', 'ED', 'Batch No'];
        foreach ($headers as $colIdx => $h) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1);
            $sheet->setCellValue("{$colLetter}1", $h);
        }

        $sheet->setCellValue('A2', '14/09/2026');
        $sheet->setCellValue('B2', $distCode);
        $sheet->setCellValue('C2', 'Item Uji Coba Email');
        $sheet->setCellValue('D2', 150);
        $sheet->setCellValue('E2', 'BTL');
        $sheet->setCellValue('F2', '15/12/2027');
        $sheet->setCellValue('G2', 'BATCH-EMAIL-001');

        $writer = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'test_excel_').'.xlsx';
        $writer->save($tempFile);
        $content = file_get_contents($tempFile);
        @unlink($tempFile);

        return $content;
    }

    public function test_can_load_stock_attachment_directly_from_email_uid(): void
    {
        $user = User::where('email', 'admin@satoriagroup.co.id')->first() ?? User::first();
        if (! $user) {
            $user = User::factory()->create();
            $user->syncRoles([User::ROLE_ADMIN]);
        }

        // Setup test distributor & item
        $dist = Distributor::firstOrCreate(
            ['distributor_code' => 'TEST_EMAIL_DIST'],
            ['name' => 'Distributor Uji Email', 'is_active' => true]
        );

        $distItem = DistributorItem::firstOrCreate(
            ['distributor_id' => $dist->id, 'item_name' => 'Item Uji Coba Email'],
            ['satuan' => 'BTL']
        );

        $excelBytes = $this->createSampleExcelContent('TEST_EMAIL_DIST');

        $mockImap = Mockery::mock(ImapService::class);
        $mockImap->shouldReceive('isConfigured')->andReturn(true);
        $mockImap->shouldReceive('getExcelAttachment')
            ->with('101', null)
            ->once()
            ->andReturn([
                'filename' => 'daily_stock.xlsx',
                'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'content' => $excelBytes,
                'size' => strlen($excelBytes),
            ]);
        $mockImap->shouldReceive('markAsRead')
            ->with('101')
            ->once()
            ->andReturn(true);

        $this->app->instance(ImapService::class, $mockImap);

        Livewire::actingAs($user)
            ->withQueryParams(['from_email_uid' => '101'])
            ->test(\App\Livewire\Stock\Upload::class)
            ->assertSet('distributorId', $dist->id)
            ->assertSet('tanggal', '2026-09-14')
            ->assertCount('rows', 1);
    }

    public function test_email_table_displays_upload_button_when_attachments_exist(): void
    {
        $user = User::where('email', 'admin@satoriagroup.co.id')->first() ?? User::first();
        if (! $user) {
            $user = User::factory()->create();
            $user->syncRoles([User::ROLE_ADMIN]);
        }

        $mockImap = Mockery::mock(ImapService::class);
        $mockImap->shouldReceive('isConfigured')->andReturn(true);
        $mockImap->shouldReceive('getInbox')->andReturn([
            'success' => true,
            'data' => [
                [
                    'uid' => '555',
                    'from_name' => 'Distributor A',
                    'from_email' => 'distA@example.com',
                    'subject' => 'Satoria Daily Stock - 14 Sep 2026',
                    'is_daily_stock' => true,
                    'date' => now()->toIso8601String(),
                    'date_display' => '14 Sep 2026, 08:00',
                    'is_read' => false,
                    'has_attachments' => true,
                    'attachment_count' => 1,
                ],
                [
                    'uid' => '777',
                    'from_name' => 'Other Sender',
                    'from_email' => 'other@example.com',
                    'subject' => 'Informasi Umum',
                    'is_daily_stock' => false,
                    'date' => now()->toIso8601String(),
                    'date_display' => '14 Sep 2026, 08:30',
                    'is_read' => true,
                    'has_attachments' => true,
                    'attachment_count' => 1,
                ],
            ],
            'total' => 2,
            'current_page' => 1,
            'per_page' => 15,
            'last_page' => 1,
        ]);

        $this->app->instance(ImapService::class, $mockImap);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Emails\Index::class)
            ->assertSee('Upload')
            ->assertSee('from_email_uid=555')
            ->assertDontSee('from_email_uid=777')
            ->assertSee('Hanya tersedia untuk email bertanda Satoria Daily Stock');
    }
}
