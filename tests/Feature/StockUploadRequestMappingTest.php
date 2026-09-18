<?php

namespace Tests\Feature;

use App\Livewire\Stock\Upload;
use App\Models\Distributor;
use App\Models\DistributorItem;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class StockUploadRequestMappingTest extends TestCase
{
    // Tanpa ini data yang dibuat test TER-COMMIT permanen ke database kerja.
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    public function test_logistik_can_submit_request_mapping_for_skipped_items(): void
    {
        $user = User::where('email', 'logistik@satoriagroup.co.id')->first();
        if (! $user) {
            $user = User::factory()->create();
            $user->syncRoles([User::ROLE_LOGISTIK]);
        }

        $distributor = Distributor::first();
        $this->assertNotNull($distributor);

        $uniqueItemName = 'TEST PROD UNMAPPED '.uniqid();

        Livewire::actingAs($user)
            ->test(Upload::class)
            ->set('distributorId', $distributor->id)
            ->set('skippedRowsData', [
                [
                    'item_name' => $uniqueItemName,
                    'satuan' => 'BOX',
                    'quantity' => 25,
                    'expired_date' => '2027-12-31',
                    'batch_no' => 'BATCH-TEST-01',
                ],
            ])
            ->call('submitRequestMapping')
            ->assertNotDispatched('rows-loaded')
            ->assertCount('rows', 0)
            ->assertSet('skippedRowsData', [])
            ->assertSet('skippedItems', [])
            ->assertSet('showRequestModal', false);

        // Assert item was created in distributor_items with netsuite_item_id = null
        $createdItem = DistributorItem::where('distributor_id', $distributor->id)
            ->where('item_name', $uniqueItemName)
            ->first();

        $this->assertNotNull($createdItem);
        $this->assertNull($createdItem->netsuite_item_id);
        $this->assertEquals('BOX', $createdItem->satuan);
        $this->assertFalse($createdItem->isMapped());

        // Cleanup
        $createdItem->forceDelete();
    }

    public function test_logistik_can_submit_single_manual_item_request(): void
    {
        $user = User::where('email', 'logistik@satoriagroup.co.id')->first();
        if (! $user) {
            $user = User::factory()->create();
            $user->syncRoles([User::ROLE_LOGISTIK]);
        }

        $distributor = Distributor::first();
        $this->assertNotNull($distributor);

        $uniqueItemName = 'MANUAL PROD '.uniqid();

        Livewire::actingAs($user)
            ->test(Upload::class)
            ->set('distributorId', $distributor->id)
            ->set('requestItemName', $uniqueItemName)
            ->set('requestSatuan', 'BTL')
            ->call('submitSingleRequest')
            ->assertNotDispatched('rows-loaded')
            ->assertCount('rows', 0)
            ->assertSet('showSingleRequestModal', false);

        $createdItem = DistributorItem::where('distributor_id', $distributor->id)
            ->where('item_name', $uniqueItemName)
            ->first();

        $this->assertNotNull($createdItem);
        $this->assertNull($createdItem->netsuite_item_id);
        $this->assertEquals('BTL', $createdItem->satuan);

        // Cleanup
        $createdItem->forceDelete();
    }

    public function test_unmapped_items_cannot_be_saved_to_stock_entries(): void
    {
        $user = User::where('email', 'logistik@satoriagroup.co.id')->first();
        if (! $user) {
            $user = User::factory()->create();
            $user->syncRoles([User::ROLE_LOGISTIK]);
        }

        $distributor = Distributor::first();
        $this->assertNotNull($distributor);

        $unmappedItem = DistributorItem::create([
            'distributor_id' => $distributor->id,
            'item_name' => 'UNMAPPED TEST FOR SAVE '.uniqid(),
            'satuan' => 'BTL',
            'netsuite_item_id' => null,
        ]);

        $testDate = '2026-09-16';

        Livewire::actingAs($user)
            ->test(Upload::class)
            ->set('distributorId', $distributor->id)
            ->set('tanggal', $testDate)
            ->call('saveRows', [
                [
                    'distributor_item_id' => $unmappedItem->id,
                    'item_name' => $unmappedItem->item_name,
                    'satuan' => 'BTL',
                    'quantity' => 100,
                    'expired_date' => '2027-12-31',
                    'batch_no' => 'B-UNMAPPED-99',
                ],
            ]);

        // Verify that StockEntry was NOT created because item is unmapped
        $entry = \App\Models\StockEntry::where('distributor_id', $distributor->id)
            ->where('distributor_item_id', $unmappedItem->id)
            ->where('tanggal', $testDate)
            ->first();

        $this->assertNull($entry, 'Unmapped item must NOT be saved to stock_entries');

        $unmappedItem->forceDelete();
    }
}
