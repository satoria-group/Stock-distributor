<?php

namespace Tests\Feature;

use App\Livewire\Stock\Upload;
use App\Models\Distributor;
use App\Models\DistributorItem;
use App\Models\NetsuiteItem;
use App\Models\StockEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Mode "Timpa/Revisi" harus benar-benar menimpa: SKU lama yang tidak ada di
 * berkas baru ikut dibuang. Tanpa itu hasilnya identik dengan mode "Gabung".
 */
class StockUploadReplaceModeTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    private Distributor $dist;

    private DistributorItem $itemA;

    private DistributorItem $itemB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::where('email', 'admin@satoriagroup.co.id')->first() ?? User::first();
        if (! $this->user) {
            $this->user = User::factory()->create();
            $this->user->syncRoles([User::ROLE_ADMIN]);
        }

        $this->dist = Distributor::create([
            'distributor_code' => 'TEST_RPL_'.uniqid(),
            'name' => 'Distributor Uji Timpa',
            'is_active' => true,
        ]);

        $this->itemA = $this->makeItem('Item A Tetap Ada');
        $this->itemB = $this->makeItem('Item B Dicoret Di Revisi');
    }

    private function makeItem(string $name): DistributorItem
    {
        $ns = NetsuiteItem::create([
            'netsuite_id' => 'NS_'.uniqid(),
            'netsuite_name' => $name,
        ]);

        return DistributorItem::create([
            'distributor_id' => $this->dist->id,
            'item_name' => $name,
            'satuan' => 'BTL',
            'netsuite_item_id' => $ns->id,
        ]);
    }

    private function seedSnapshot(string $tanggal): void
    {
        foreach ([$this->itemA, $this->itemB] as $item) {
            StockEntry::create([
                'tanggal' => $tanggal,
                'distributor_id' => $this->dist->id,
                'distributor_item_id' => $item->id,
                'quantity' => 100,
                'satuan' => 'BTL',
            ]);
        }
    }

    public function test_replace_mode_deletes_rows_absent_from_the_new_file(): void
    {
        $tanggal = '2026-09-20';
        $this->seedSnapshot($tanggal);

        // Berkas revisi hanya berisi Item A; Item B dicoret.
        Livewire::actingAs($this->user)
            ->test(Upload::class)
            ->set('tanggal', $tanggal)
            ->set('distributorId', $this->dist->id)
            ->set('replaceExistingSnapshot', true)
            ->call('saveRows', [[
                'distributor_item_id' => $this->itemA->id,
                'satuan' => 'BTL',
                'quantity' => 250,
                'expired_date' => null,
                'batch_no' => null,
            ]]);

        $this->assertDatabaseHas('stock_entries', [
            'tanggal' => $tanggal,
            'distributor_item_id' => $this->itemA->id,
            'quantity' => 250.00,
        ]);

        // Kalau baris ini masih ada, mode Timpa tidak menimpa apa pun —
        // hasilnya sama saja dengan mode Gabung.
        $this->assertDatabaseMissing('stock_entries', [
            'tanggal' => $tanggal,
            'distributor_item_id' => $this->itemB->id,
        ]);
    }

    public function test_normal_save_does_not_delete_rows_absent_from_the_grid(): void
    {
        $tanggal = '2026-09-21';
        $this->seedSnapshot($tanggal);

        // Tanpa mode timpa, grid yang hanya memuat sebagian data TIDAK BOLEH
        // menghapus baris yang tidak pernah dilihat pengguna.
        Livewire::actingAs($this->user)
            ->test(Upload::class)
            ->set('tanggal', $tanggal)
            ->set('distributorId', $this->dist->id)
            ->call('saveRows', [[
                'distributor_item_id' => $this->itemA->id,
                'satuan' => 'BTL',
                'quantity' => 250,
                'expired_date' => null,
                'batch_no' => null,
            ]]);

        $this->assertDatabaseHas('stock_entries', [
            'tanggal' => $tanggal,
            'distributor_item_id' => $this->itemB->id,
        ]);
    }

    public function test_confirm_import_sets_replace_flag_only_for_replace_mode(): void
    {
        $tanggal = '2026-09-22';
        $this->seedSnapshot($tanggal);

        $pending = [
            'tanggal' => $tanggal,
            'distributor_id' => $this->dist->id,
            'distributor_name' => $this->dist->name,
            'existing_count' => 2,
            'new_rows' => [],
            'skipped' => [],
            'skipped_rows_data' => [],
        ];

        Livewire::actingAs($this->user)
            ->test(Upload::class)
            ->set('pendingImportData', $pending)
            ->call('confirmImport', 'replace')
            ->assertSet('replaceExistingSnapshot', true);

        Livewire::actingAs($this->user)
            ->test(Upload::class)
            ->set('pendingImportData', $pending)
            ->call('confirmImport', 'merge')
            ->assertSet('replaceExistingSnapshot', false);
    }
}
