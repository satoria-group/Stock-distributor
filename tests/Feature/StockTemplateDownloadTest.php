<?php

namespace Tests\Feature;

use App\Livewire\Stock\Upload;
use App\Models\User;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class StockTemplateDownloadTest extends TestCase
{
    public function test_template_download_has_correct_format_and_placeholders(): void
    {
        $user = User::where('email', 'logistik@satoriagroup.co.id')->first() ?? User::first();
        $user->syncPermissions(['stock.view', 'stock.upload']);

        $this->actingAs($user);
        $component = new Upload();
        $response = $component->downloadTemplate();

        $this->assertEquals(200, $response->getStatusCode());

        // Capture streamed output
        ob_start();
        $response->sendContent();
        $excelContent = ob_get_clean();

        $tempFile = tempnam(sys_get_temp_dir(), 'tpl_test_') . '.xlsx';
        file_put_contents($tempFile, $excelContent);

        $spreadsheet = IOFactory::load($tempFile);
        $sheet = $spreadsheet->getSheetByName('Template');
        $this->assertNotNull($sheet, "Sheet 'Template' must exist.");

        $data = $sheet->toArray();

        // Row 1: Headers
        $expectedHeaders = ['Tanggal', 'ID DISTRIBUTOR', 'Distributor Item Name', 'Quantity', 'Satuan', 'ED', 'Batch No'];
        $this->assertEquals($expectedHeaders, array_slice($data[0], 0, 7));

        // Row 2: Placeholder
        $expectedRow2 = ['DD/MM/YYYY', 'xxxx', 'xxxxx xxxx', 'xxx', null, 'DD/MM/YYYY', 'xxx'];
        $this->assertEquals($expectedRow2, array_slice($data[1], 0, 7));

        // Row 3: Placeholder continuation
        $expectedRow3 = [null, null, 'xxxxx xxxx', 'xxx', null, 'DD/MM/YYYY', 'xxx'];
        $this->assertEquals($expectedRow3, array_slice($data[2], 0, 7));

        // Sheet 2: Daftar Distributor
        $this->assertNotNull($spreadsheet->getSheetByName('Daftar Distributor'));

        // Sheet 3: Panduan Pengisian
        $this->assertNotNull($spreadsheet->getSheetByName('Panduan Pengisian'));

        @unlink($tempFile);
    }

    public function test_import_rejects_unmodified_template_with_helpful_error(): void
    {
        $user = User::where('email', 'logistik@satoriagroup.co.id')->first() ?? User::first();
        $user->syncPermissions(['stock.view', 'stock.upload']);

        $this->actingAs($user);
        $component = new Upload();
        $response = $component->downloadTemplate();

        ob_start();
        $response->sendContent();
        $excelContent = ob_get_clean();

        $tempFile = tempnam(sys_get_temp_dir(), 'tpl_import_') . '.xlsx';
        file_put_contents($tempFile, $excelContent);

        $uploadedFile = \Illuminate\Http\UploadedFile::fake()->createWithContent(
            'template_upload_stock_satoria.xlsx',
            $excelContent
        );

        Livewire::actingAs($user)
            ->test(Upload::class)
            ->set('file', $uploadedFile)
            ->call('importFile')
            ->assertHasErrors(['file']);

        @unlink($tempFile);
    }

    public function test_import_skips_placeholder_rows_when_real_data_exists(): void
    {
        $user = User::where('email', 'logistik@satoriagroup.co.id')->first() ?? User::first();
        $user->syncPermissions(['stock.view', 'stock.upload']);

        $distributor = \App\Models\Distributor::first();
        $this->assertNotNull($distributor);

        // Create or get distributor item
        $distItem = \App\Models\DistributorItem::firstOrCreate([
            'distributor_id' => $distributor->id,
            'item_name' => 'REAL PRODUCT TEST 1',
        ], [
            'satuan' => 'BOTOL',
        ]);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template');

        // Headers
        $sheet->fromArray(['Tanggal', 'ID DISTRIBUTOR', 'Distributor Item Name', 'Quantity', 'Satuan', 'ED', 'Batch No'], null, 'A1');
        // Row 2-3: Placeholders with DD/MM/YYYY
        $sheet->fromArray(['DD/MM/YYYY', 'xxxx', 'xxxxx xxxx', 'xxx', '', 'DD/MM/YYYY', 'xxx'], null, 'A2');
        $sheet->fromArray(['', '', 'xxxxx xxxx', 'xxx', '', 'DD/MM/YYYY', 'xxx'], null, 'A3');
        // Row 4: Real data with DD/MM/YYYY date
        $sheet->fromArray(['13/09/2026', $distributor->distributor_code, 'REAL PRODUCT TEST 1', 50, 'BOTOL', '31/12/2027', 'BATCH001'], null, 'A4');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'tpl_real_') . '.xlsx';
        $writer->save($tempFile);

        $uploadedFile = \Illuminate\Http\UploadedFile::fake()->createWithContent(
            'real_stock.xlsx',
            file_get_contents($tempFile)
        );

        $test = Livewire::actingAs($user)
            ->test(Upload::class)
            ->set('file', $uploadedFile)
            ->call('importFile')
            ->assertHasNoErrors();

        // Verify rows array has 1 item and expired_date is formatted as DD/MM/YYYY
        $rows = $test->get('rows');
        $this->assertCount(1, $rows);
        $this->assertEquals('REAL PRODUCT TEST 1', reset($rows)['item_name']);
        $this->assertEquals(50, reset($rows)['quantity']);
        $this->assertEquals('31/12/2027', reset($rows)['expired_date']);

        @unlink($tempFile);
    }

    public function test_load_existing_formats_expired_date_as_dd_mm_yyyy_and_dispatches_event(): void
    {
        $user = User::where('email', 'logistik@satoriagroup.co.id')->first() ?? User::first();
        $user->syncPermissions(['stock.view', 'stock.upload']);

        $distributor = \App\Models\Distributor::where('name', 'like', '%PUSAT%')->first() ?? \App\Models\Distributor::first();
        $this->assertNotNull($distributor);

        $test = Livewire::actingAs($user)
            ->test(Upload::class)
            ->set('tanggal', '2026-09-02')
            ->set('distributorId', $distributor->id)
            ->call('loadExisting')
            ->assertDispatched('rows-loaded');

        $rows = $test->get('rows');
        if (count($rows) > 0) {
            foreach ($rows as $r) {
                if (! empty($r['expired_date'])) {
                    // Must be in DD/MM/YYYY format
                    $this->assertMatchesRegularExpression('/^\d{2}\/\d{2}\/\d{4}$/', $r['expired_date']);
                }
            }
        }
    }
}
