<?php

namespace Tests\Feature;

use App\Livewire\Stock\History;
use App\Models\Distributor;
use App\Models\DistributorItem;
use App\Models\StockEntry;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Livewire\Livewire;
use Tests\TestCase;

class StockHistorySnapshotDeleteTest extends TestCase
{
    protected function getLogistikUser(): User
    {
        $user = User::where('email', 'logistik@satoriagroup.co.id')->first();
        if (! $user) {
            $user = User::factory()->create();
            $user->syncRoles([User::ROLE_LOGISTIK]);
        }

        return $user;
    }

    protected function getViewerUser(): User
    {
        $user = User::where('email', 'test_viewer@satoria.com')->first();
        if (! $user) {
            $user = User::factory()->create([
                'email' => 'test_viewer@satoria.com',
            ]);
        }
        $user->syncRoles([]);
        $user->syncPermissions(['stock.view']);

        return $user;
    }

    public function test_logistik_can_open_confirm_delete_modal_and_cancel(): void
    {
        $user = $this->getLogistikUser();
        $distributor = Distributor::first();
        $this->assertNotNull($distributor);

        $testDate = '2026-03-01';

        StockEntry::where('tanggal', $testDate)->where('distributor_id', $distributor->id)->forceDelete();

        $item = DistributorItem::firstOrCreate(
            ['distributor_id' => $distributor->id, 'item_name' => 'TEST SKU FOR DELETE MODAL'],
            ['satuan' => 'BTL']
        );

        $entry = StockEntry::create([
            'distributor_id' => $distributor->id,
            'distributor_item_id' => $item->id,
            'tanggal' => $testDate,
            'satuan' => 'BTL',
            'quantity' => 150,
            'uploaded_by' => $user->id,
        ]);

        try {
            Livewire::actingAs($user)
                ->test(History::class)
                ->call('confirmDeleteSnapshot', $testDate, $distributor->id)
                ->assertSet('showDeleteModal', true)
                ->assertSet('deleteTanggal', $testDate)
                ->assertSet('deleteDistributorId', $distributor->id)
                ->assertSet('deleteDistributorName', $distributor->name)
                ->assertSet('deleteTotalSku', 1)
                ->assertSet('deleteTotalQuantity', 150.0)
                ->call('cancelDeleteSnapshot')
                ->assertSet('showDeleteModal', false)
                ->assertSet('deleteTanggal', null)
                ->assertSet('deleteDistributorId', null);
        } finally {
            $entry->forceDelete();
        }
    }

    public function test_logistik_can_delete_snapshot_and_flash_session_status(): void
    {
        $user = $this->getLogistikUser();
        $distributor = Distributor::first();
        $this->assertNotNull($distributor);

        $testDate = '2026-03-02';
        $otherDate = '2026-03-03';

        StockEntry::where('tanggal', $testDate)->where('distributor_id', $distributor->id)->forceDelete();
        StockEntry::where('tanggal', $otherDate)->where('distributor_id', $distributor->id)->forceDelete();

        $item1 = DistributorItem::firstOrCreate(
            ['distributor_id' => $distributor->id, 'item_name' => 'TEST SKU 1 FOR DELETE'],
            ['satuan' => 'BTL']
        );
        $item2 = DistributorItem::firstOrCreate(
            ['distributor_id' => $distributor->id, 'item_name' => 'TEST SKU 2 FOR DELETE'],
            ['satuan' => 'BOX']
        );

        // Entries for testDate snapshot
        $entry1 = StockEntry::create([
            'distributor_id' => $distributor->id,
            'distributor_item_id' => $item1->id,
            'tanggal' => $testDate,
            'satuan' => 'BTL',
            'quantity' => 200,
            'uploaded_by' => $user->id,
        ]);
        $entry2 = StockEntry::create([
            'distributor_id' => $distributor->id,
            'distributor_item_id' => $item2->id,
            'tanggal' => $testDate,
            'satuan' => 'BOX',
            'quantity' => 100,
            'uploaded_by' => $user->id,
        ]);

        // Entry for otherDate snapshot (should NOT be deleted)
        $entryOther = StockEntry::create([
            'distributor_id' => $distributor->id,
            'distributor_item_id' => $item1->id,
            'tanggal' => $otherDate,
            'satuan' => 'BTL',
            'quantity' => 500,
            'uploaded_by' => $user->id,
        ]);

        try {
            Livewire::actingAs($user)
                ->test(History::class)
                ->call('confirmDeleteSnapshot', $testDate, $distributor->id)
                ->assertSet('showDeleteModal', true)
                ->assertSet('deleteTotalSku', 2)
                ->assertSet('deleteTotalQuantity', 300.0)
                ->call('deleteSnapshot')
                ->assertSet('showDeleteModal', false)
                ->assertSee('berhasil dihapus');

            // Verify entries for testDate are deleted
            $this->assertEquals(0, StockEntry::where('tanggal', $testDate)->where('distributor_id', $distributor->id)->count());

            // Verify entries for otherDate are still intact
            $this->assertEquals(1, StockEntry::where('tanggal', $otherDate)->where('distributor_id', $distributor->id)->count());
        } finally {
            StockEntry::where('tanggal', $testDate)->where('distributor_id', $distributor->id)->forceDelete();
            StockEntry::where('tanggal', $otherDate)->where('distributor_id', $distributor->id)->forceDelete();
        }
    }

    public function test_user_without_upload_permission_cannot_delete_snapshot(): void
    {
        $user = $this->getViewerUser();
        $distributor = Distributor::first();
        $this->assertNotNull($distributor);

        try {
            Livewire::actingAs($user)
                ->test(History::class)
                ->call('confirmDeleteSnapshot', '2026-03-01', $distributor->id)
                ->assertForbidden();
        } finally {
            $user->delete();
        }
    }
}
