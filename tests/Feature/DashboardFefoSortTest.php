<?php

namespace Tests\Feature;

use App\Livewire\Dashboard;
use App\Models\Distributor;
use App\Models\DistributorItem;
use App\Models\StockEntry;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardFefoSortTest extends TestCase
{
    // Tanpa ini data yang dibuat test TER-COMMIT permanen ke database kerja.
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    protected function getLogistikUser(): User
    {
        $user = User::where('email', 'logistik@satoriagroup.co.id')->first();
        if (! $user) {
            $user = User::factory()->create();
            $user->syncRoles([User::ROLE_LOGISTIK]);
        }

        return $user;
    }

    public function test_fefo_table_has_sortable_columns_and_default_fefo_order(): void
    {
        $user = $this->getLogistikUser();

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->set('activeTab', 'expiry')
            ->assertSee('Daftar Batch Terurut Kedaluwarsa (FEFO Order)')
            ->assertSee('wire:click="setFefoSort(\'item_name\')"', false)
            ->assertSee('wire:click="setFefoSort(\'distributor\')"', false)
            ->assertSee('wire:click="setFefoSort(\'batch_no\')"', false)
            ->assertSee('wire:click="setFefoSort(\'expired_date\')"', false)
            ->assertSee('wire:click="setFefoSort(\'days\')"', false)
            ->assertSee('wire:click="setFefoSort(\'quantity\')"', false)
            ->assertSee('wire:click="setFefoSort(\'tier\')"', false)
            ->assertSet('fefoSortBy', 'days')
            ->assertSet('fefoSortDir', 'asc');
    }

    public function test_fefo_sorting_toggles_direction_and_changes_column(): void
    {
        $user = $this->getLogistikUser();

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->set('activeTab', 'expiry')
            // Default is days asc
            ->assertSet('fefoSortBy', 'days')
            ->assertSet('fefoSortDir', 'asc')
            // Click item_name -> item_name asc
            ->call('setFefoSort', 'item_name')
            ->assertSet('fefoSortBy', 'item_name')
            ->assertSet('fefoSortDir', 'asc')
            ->assertSee('Sortir:')
            ->assertSee('Nama Produk')
            // Click item_name again -> item_name desc
            ->call('setFefoSort', 'item_name')
            ->assertSet('fefoSortBy', 'item_name')
            ->assertSet('fefoSortDir', 'desc')
            // Click quantity -> quantity asc
            ->call('setFefoSort', 'quantity')
            ->assertSet('fefoSortBy', 'quantity')
            ->assertSet('fefoSortDir', 'asc')
            // Click distributor -> distributor asc
            ->call('setFefoSort', 'distributor')
            ->assertSet('fefoSortBy', 'distributor')
            ->assertSet('fefoSortDir', 'asc')
            // Click expired_date -> expired_date asc
            ->call('setFefoSort', 'expired_date')
            ->assertSet('fefoSortBy', 'expired_date')
            ->assertSet('fefoSortDir', 'asc')
            // Click tier -> tier asc
            ->call('setFefoSort', 'tier')
            ->assertSet('fefoSortBy', 'tier')
            ->assertSet('fefoSortDir', 'asc')
            // Reset filters -> back to default days asc
            ->call('resetFefoFilters')
            ->assertSet('fefoSortBy', 'days')
            ->assertSet('fefoSortDir', 'asc');
    }

    public function test_tabs_have_isolated_independent_filters(): void
    {
        $user = $this->getLogistikUser();

        $comp = Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->set('activeTab', 'expiry')
            ->assertSee('wire:model.live="fefoBranchId"', false)
            ->assertSee('wire:model.live="fefoSatuanFilter"', false);

        // Set Tab 1 filters
        $comp->set('selectedBranchId', 999)
             ->set('satuanFilter', 'BTL');

        // Tab 2 filters remain independent
        $comp->assertSet('fefoBranchId', null)
             ->assertSet('fefoSatuanFilter', '');

        // Set Tab 2 filters
        $comp->set('fefoBranchId', 888)
             ->set('fefoSatuanFilter', 'AMP');

        // Tab 1 filters remain unchanged
        $comp->assertSet('selectedBranchId', 999)
             ->assertSet('satuanFilter', 'BTL');

        // Calling resetFilters resets only Tab 1
        $comp->call('resetFilters');
        $comp->assertSet('selectedBranchId', null)
             ->assertSet('satuanFilter', '')
             ->assertSet('fefoBranchId', 888)
             ->assertSet('fefoSatuanFilter', 'AMP');

        // Calling resetFefoFilters resets only Tab 2
        $comp->call('resetFefoFilters');
        $comp->assertSet('fefoBranchId', null)
             ->assertSet('fefoSatuanFilter', '')
             ->assertSet('fefoSortBy', 'days')
             ->assertSet('fefoSortDir', 'asc');

        // Verify that Tab 1 branch filter does not constrain Tab 2 data
        $dist = Distributor::first();
        if ($dist) {
            $comp->set('selectedBranchId', $dist->id);
            $stockTableFiltered = $comp->viewData('stockTable');
            foreach ($stockTableFiltered as $row) {
                $this->assertEquals($dist->id, $row->entry->distributor_id);
            }
            // Tab 2 has fefoBranchId null, so fefoTable is not restricted to $dist->id
            $comp->assertSet('fefoBranchId', null);
        }
    }

    public function test_fefo_sorting_reorders_actual_data(): void
    {
        $user = $this->getLogistikUser();

        $comp = Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->set('activeTab', 'expiry');

        $fefoTable = $comp->viewData('fefoTable');
        if ($fefoTable && $fefoTable->count() >= 2) {
            // Sort by quantity asc
            $comp->call('setFefoSort', 'quantity');
            $sortedAsc = $comp->viewData('fefoTable');
            $firstQtyAsc = (float) $sortedAsc->first()->entry->quantity;
            $secondQtyAsc = (float) $sortedAsc->get(1)->entry->quantity;
            $this->assertLessThanOrEqual($secondQtyAsc, $firstQtyAsc);

            // Sort by quantity desc
            $comp->call('setFefoSort', 'quantity');
            $sortedDesc = $comp->viewData('fefoTable');
            $firstQtyDesc = (float) $sortedDesc->first()->entry->quantity;
            $secondQtyDesc = (float) $sortedDesc->get(1)->entry->quantity;
            $this->assertGreaterThanOrEqual($secondQtyDesc, $firstQtyDesc);
        }
    }
}
