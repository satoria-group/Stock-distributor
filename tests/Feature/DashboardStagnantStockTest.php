<?php

namespace Tests\Feature;

use App\Livewire\Dashboard;
use App\Models\Distributor;
use App\Models\DistributorItem;
use App\Models\StockEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardStagnantStockTest extends TestCase
{
    use DatabaseTransactions;

    protected function getLogistikUser(): User
    {
        $user = User::where('email', 'logistik@satoriagroup.co.id')->first();
        if (! $user) {
            $user = User::factory()->create();
            $user->syncRoles([User::ROLE_LOGISTIK]);
        }

        return $user;
    }

    public function test_stagnant_stock_tab_renders_with_default_filters_and_kpi(): void
    {
        $user = $this->getLogistikUser();

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->set('activeTab', 'stagnant')
            ->assertSee('TOTAL SKU MENGENDAP')
            ->assertSee('MACET TOTAL (DEAD STOCK)')
            ->assertSee('TOTAL VOLUME TERKUNCI')
            ->assertSet('stagnantPeriod', 30)
            ->assertSet('stagnantRiskFilter', 'all')
            ->assertSet('stagnantSortBy', 'days_stagnant')
            ->assertSet('stagnantSortDir', 'desc');
    }

    public function test_stagnant_stock_correctly_classifies_dead_and_slow_moving(): void
    {
        $distributor = Distributor::create([
            'distributor_code' => 'KFTD-TEST-STAGNANT',
            'name' => 'KFTD Cabang Test Stagnant',
            'city' => 'Surabaya',
            'province' => 'Jawa Timur',
            'status' => 'active',
        ]);

        $itemDead = DistributorItem::create([
            'distributor_id' => $distributor->id,
            'item_code' => 'ITEM-DEAD-01',
            'item_name' => 'Produk Macet Total Botol',
        ]);

        $itemSlow = DistributorItem::create([
            'distributor_id' => $distributor->id,
            'item_code' => 'ITEM-SLOW-01',
            'item_name' => 'Produk Pergerakan Lambat Ampul',
        ]);

        $itemNormal = DistributorItem::create([
            'distributor_id' => $distributor->id,
            'item_code' => 'ITEM-NORM-01',
            'item_name' => 'Produk Laris Cepat Normal',
        ]);

        $startDate = '2026-08-15';
        $endDate = '2026-09-02';

        // 1. Item Dead: 500 at start, 500 at end (Zero outflow)
        StockEntry::create([
            'distributor_id' => $distributor->id,
            'distributor_item_id' => $itemDead->id,
            'tanggal' => $startDate,
            'satuan' => 'BOTOL',
            'quantity' => 500,
            'batch_no' => 'B-DEAD',
            'expired_date' => '2026-11-30', // < 90 days => critical ED!
        ]);
        StockEntry::create([
            'distributor_id' => $distributor->id,
            'distributor_item_id' => $itemDead->id,
            'tanggal' => $endDate,
            'satuan' => 'BOTOL',
            'quantity' => 500,
            'batch_no' => 'B-DEAD',
            'expired_date' => '2026-11-30',
        ]);

        // 2. Item Slow: 1000 at start, 980 at end (Outflow = 20, which is 2% < 10%)
        StockEntry::create([
            'distributor_id' => $distributor->id,
            'distributor_item_id' => $itemSlow->id,
            'tanggal' => $startDate,
            'satuan' => 'AMP',
            'quantity' => 1000,
            'batch_no' => 'B-SLOW',
            'expired_date' => '2028-12-31',
        ]);
        StockEntry::create([
            'distributor_id' => $distributor->id,
            'distributor_item_id' => $itemSlow->id,
            'tanggal' => $endDate,
            'satuan' => 'AMP',
            'quantity' => 980,
            'batch_no' => 'B-SLOW',
            'expired_date' => '2028-12-31',
        ]);

        // 3. Item Normal: 1000 at start, 600 at end (Outflow = 400, which is 40% >= 10%)
        StockEntry::create([
            'distributor_id' => $distributor->id,
            'distributor_item_id' => $itemNormal->id,
            'tanggal' => $startDate,
            'satuan' => 'BOTOL',
            'quantity' => 1000,
            'batch_no' => 'B-NORM',
            'expired_date' => '2028-12-31',
        ]);
        StockEntry::create([
            'distributor_id' => $distributor->id,
            'distributor_item_id' => $itemNormal->id,
            'tanggal' => $endDate,
            'satuan' => 'BOTOL',
            'quantity' => 600,
            'batch_no' => 'B-NORM',
            'expired_date' => '2028-12-31',
        ]);

        $user = $this->getLogistikUser();

        $comp = Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->set('activeTab', 'stagnant')
            ->set('stagnantBranchId', $distributor->id);

        // Verify stagnant table contents directly
        $stagnantTable = $comp->viewData('stagnantTable');
        $this->assertEquals(2, $stagnantTable->total());

        $itemNames = collect($stagnantTable->items())->pluck('distributorItem.item_name')->all();
        $this->assertContains('Produk Macet Total Botol', $itemNames);
        $this->assertContains('Produk Pergerakan Lambat Ampul', $itemNames);
        $this->assertNotContains('Produk Laris Cepat Normal', $itemNames);

        // Verify dead stock action recommendation (Dead + Critical ED)
        $comp->assertSee('Prioritas Retur / Penjualan Cepat');

        // Test filter only dead
        $comp->set('stagnantRiskFilter', 'dead');
        $deadTable = $comp->viewData('stagnantTable');
        $this->assertEquals(1, $deadTable->total());
        $this->assertEquals('Produk Macet Total Botol', $deadTable->items()[0]->distributorItem->item_name);

        // Test filter only slow
        $comp->set('stagnantRiskFilter', 'slow');
        $slowTable = $comp->viewData('stagnantTable');
        $this->assertEquals(1, $slowTable->total());
        $this->assertEquals('Produk Pergerakan Lambat Ampul', $slowTable->items()[0]->distributorItem->item_name);

        // Test filter critical ED
        $comp->set('stagnantRiskFilter', 'critical_ed');
        $criticalTable = $comp->viewData('stagnantTable');
        $this->assertEquals(1, $criticalTable->total());
        $this->assertEquals('Produk Macet Total Botol', $criticalTable->items()[0]->distributorItem->item_name);
    }

    public function test_stagnant_stock_filters_are_isolated_from_other_tabs(): void
    {
        $user = $this->getLogistikUser();

        $comp = Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->set('activeTab', 'stagnant')
            ->set('stagnantBranchId', 123)
            ->set('stagnantSatuanFilter', 'AMP')
            ->set('stagnantSearch', 'Injeksi');

        // Tab 1 & Tab 2 filters should remain completely untouched
        $comp->assertSet('selectedBranchId', null)
             ->assertSet('satuanFilter', '')
             ->assertSet('search', '')
             ->assertSet('fefoBranchId', null)
             ->assertSet('fefoSatuanFilter', '');

        // Calling resetStagnantFilters should reset only stagnant filters
        $comp->call('resetStagnantFilters')
             ->assertSet('stagnantBranchId', null)
             ->assertSet('stagnantSatuanFilter', '')
             ->assertSet('stagnantSearch', '')
             ->assertSet('stagnantPeriod', 30);
    }

    public function test_stagnant_stock_sorting_and_export_csv(): void
    {
        $user = $this->getLogistikUser();

        $comp = Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->set('activeTab', 'stagnant')
            ->assertSet('stagnantSortBy', 'days_stagnant')
            ->assertSet('stagnantSortDir', 'desc')
            ->call('setStagnantSort', 'days_stagnant')
            ->assertSet('stagnantSortDir', 'asc')
            ->call('setStagnantSort', 'quantity')
            ->assertSet('stagnantSortBy', 'quantity')
            ->assertSet('stagnantSortDir', 'desc');

        // Test CSV export
        $comp->call('exportStagnantCsv')
            ->assertFileDownloaded('stock-macet-slow-moving-' . now()->format('Y-m-d') . '.csv');
    }
}
