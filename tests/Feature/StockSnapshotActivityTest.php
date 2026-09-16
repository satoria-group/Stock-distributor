<?php

namespace Tests\Feature;

use App\Livewire\Stock\History;
use App\Livewire\Stock\Upload;
use App\Models\Distributor;
use App\Models\DistributorItem;
use App\Models\NetsuiteItem;
use App\Models\StockEntry;
use App\Models\StockSnapshotActivity;
use App\Models\User;
use App\Services\StockImportService;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class StockSnapshotActivityTest extends TestCase
{
    public function test_email_import_logs_automation_activity(): void
    {
        $distributor = Distributor::firstOrCreate([
            'distributor_code' => 'TEST-ACT-01',
        ], [
            'name' => 'PT Test Activity Distributor',
            'is_active' => true,
            'sender_email' => 'sender@testdist.co.id',
        ]);

        $ns = NetsuiteItem::firstOrCreate([
            'netsuite_id' => 'NS-ACT-01',
        ], [
            'netsuite_name' => 'Netsuite Act 01',
            'default_satuan' => 'BOTOL',
        ]);

        $distItem = DistributorItem::firstOrCreate([
            'distributor_id' => $distributor->id,
            'item_name' => 'ITEM ACTIVITY TEST 1',
        ], [
            'satuan' => 'BOTOL',
            'netsuite_item_id' => $ns->id,
        ]);

        $testDate = '2029-11-20';
        StockEntry::where('distributor_id', $distributor->id)->where('tanggal', $testDate)->delete();
        StockSnapshotActivity::where('distributor_id', $distributor->id)->where('tanggal', $testDate)->delete();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray(['Tanggal', 'ID DISTRIBUTOR', 'Distributor Item Name', 'Quantity', 'Satuan', 'ED', 'Batch No'], null, 'A1');
        $sheet->fromArray(['20/11/2029', 'TEST-ACT-01', 'ITEM ACTIVITY TEST 1', 100, 'BOTOL', '31/12/2030', 'BATCH-ACT-1'], null, 'A2');

        $writer = new Xlsx($spreadsheet);
        $tmp = tempnam(sys_get_temp_dir(), 'act_test_') . '.xlsx';
        $writer->save($tmp);

        $service = app(StockImportService::class);
        $result = $service->parseAndImportSpreadsheet($tmp, 'sender@testdist.co.id', null, false, true);

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['imported_rows']);

        // Check activity created
        $act = StockSnapshotActivity::where('distributor_id', $distributor->id)
            ->where('tanggal', $testDate)
            ->first();

        $this->assertNotNull($act);
        $this->assertEquals('automation', $act->action);
        $this->assertNull($act->user_id);
        $this->assertStringContainsString('Import otomatis', $act->description);

        @unlink($tmp);
    }

    public function test_history_shows_automation_and_can_be_marked_as_reviewed(): void
    {
        $user = User::where('email', 'logistik@satoriagroup.co.id')->first() ?? User::first();
        $user->syncPermissions(['stock.view', 'stock.upload']);

        $distributor = Distributor::where('distributor_code', 'TEST-ACT-01')->first() ?? Distributor::first();
        $testDate = '2029-11-20';

        StockSnapshotActivity::where('distributor_id', $distributor->id)->where('tanggal', $testDate)->delete();

        $distItem = DistributorItem::where('distributor_id', $distributor->id)->first();
        if (! $distItem) {
            $distItem = DistributorItem::create([
                'distributor_id' => $distributor->id,
                'item_name' => 'ITEM ACTIVITY TEST 1',
                'satuan' => 'BOTOL',
            ]);
        }

        StockEntry::updateOrCreate([
            'tanggal' => $testDate,
            'distributor_item_id' => $distItem->id,
        ], [
            'distributor_id' => $distributor->id,
            'quantity' => 100,
            'satuan' => 'BOTOL',
            'uploaded_by' => null,
        ]);

        // Pastikan ada activity automation
        StockSnapshotActivity::firstOrCreate([
            'tanggal' => $testDate,
            'distributor_id' => $distributor->id,
            'action' => 'automation',
        ], [
            'description' => 'Import otomatis via Email',
        ]);

        // Uji komponen History
        $test = Livewire::actingAs($user)
            ->test(History::class)
            ->set('startDate', '2029-11-20')
            ->set('endDate', '2029-11-20');

        $snapshots = $test->viewData('snapshots');
        $snapshot = collect($snapshots->items())->firstWhere('distributor_id', $distributor->id);

        $this->assertNotNull($snapshot);
        $this->assertTrue($snapshot->is_automation);
        $this->assertFalse($snapshot->is_reviewed);
        $this->assertEquals('Otomasi Email', $snapshot->uploader_display);

        // Tandai sudah di-review
        $test->call('markAsReviewed', $testDate, $distributor->id)
            ->assertHasNoErrors();

        // Refresh query di component
        $test->set('startDate', '2029-11-20');
        $snapshotsAfter = $test->viewData('snapshots');
        $snapshotAfter = collect($snapshotsAfter->items())->firstWhere('distributor_id', $distributor->id);

        $this->assertTrue($snapshotAfter->is_reviewed);
        $this->assertEquals("Auto: {$user->name}", $snapshotAfter->uploader_display);

        // Verifikasi detail modal memuat jejak audit
        $test->call('viewDetail', $testDate, $distributor->id);
        $detail = $test->get('selectedSnapshot');
        $this->assertNotNull($detail);
        $this->assertTrue($detail['is_reviewed']);
        $this->assertEquals($user->name, $detail['reviewer_name']);
        $this->assertNotEmpty($detail['activities']);
    }

    public function test_manual_save_rows_logs_upload_and_edit_activities(): void
    {
        $user = User::where('email', 'logistik@satoriagroup.co.id')->first() ?? User::first();
        $user->syncPermissions(['stock.view', 'stock.upload']);

        $distributor = Distributor::first();
        $testDate = '2029-12-01';

        $ns = NetsuiteItem::firstOrCreate([
            'netsuite_id' => 'NS-ACT-SAVE',
        ], [
            'netsuite_name' => 'Netsuite Act Save',
            'default_satuan' => 'BOTOL',
        ]);

        $item = DistributorItem::firstOrCreate([
            'distributor_id' => $distributor->id,
            'item_name' => 'ITEM MANUAL SAVE TEST',
        ], [
            'satuan' => 'BOTOL',
            'netsuite_item_id' => $ns->id,
        ]);

        StockEntry::where('distributor_id', $distributor->id)->where('tanggal', $testDate)->delete();
        StockSnapshotActivity::where('distributor_id', $distributor->id)->where('tanggal', $testDate)->delete();

        // 1. Initial save (upload)
        Livewire::actingAs($user)
            ->test(Upload::class)
            ->set('tanggal', $testDate)
            ->set('distributorId', $distributor->id)
            ->call('saveRows', [
                [
                    'distributor_item_id' => $item->id,
                    'item_name' => $item->item_name,
                    'satuan' => 'BOTOL',
                    'quantity' => 50,
                    'expired_date' => '31/12/2030',
                    'batch_no' => 'B-001',
                    'mapped' => true,
                ],
            ])
            ->assertHasNoErrors();

        $actUpload = StockSnapshotActivity::where('distributor_id', $distributor->id)
            ->where('tanggal', $testDate)
            ->where('action', 'upload')
            ->first();

        $this->assertNotNull($actUpload);
        $this->assertEquals($user->id, $actUpload->user_id);
        $this->assertStringContainsString('Upload snapshot baru', $actUpload->description);

        // 2. Secondary save (edit)
        Livewire::actingAs($user)
            ->test(Upload::class)
            ->set('tanggal', $testDate)
            ->set('distributorId', $distributor->id)
            ->call('saveRows', [
                [
                    'distributor_item_id' => $item->id,
                    'item_name' => $item->item_name,
                    'satuan' => 'BOTOL',
                    'quantity' => 75,
                    'expired_date' => '31/12/2030',
                    'batch_no' => 'B-001',
                    'mapped' => true,
                ],
            ])
            ->assertHasNoErrors();

        $actEdit = StockSnapshotActivity::where('distributor_id', $distributor->id)
            ->where('tanggal', $testDate)
            ->where('action', 'edit')
            ->first();

        $this->assertNotNull($actEdit);
        $this->assertEquals($user->id, $actEdit->user_id);
        $this->assertStringContainsString('Koreksi/Update snapshot', $actEdit->description);
    }

    public function test_login_page_renders_unhide_button_and_capslock_notice(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('togglePasswordBtn');
        $response->assertSee('capsLockAlert');
        $response->assertSee('Caps Lock');
    }
}
