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

class DashboardStockTrendTest extends TestCase
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

    public function test_stock_trend_default_properties_and_rendered_elements(): void
    {
        $user = $this->getLogistikUser();

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->assertSet('trendUnit', 'BTL')
            ->assertSet('trendPeriod', 30)
            ->assertSee('Trend Stock On Hand')
            ->assertSee('Botol (BTL)')
            ->assertSee('Ampul (AMP)')
            ->assertSee('Pcs/Box (PCS)')
            ->assertSee('7 Hari')
            ->assertSee('30 Hari')
            ->assertSee('90 Hari')
            ->assertSee('Stok Posisi Terkini')
            ->assertSee('Stok Awal Periode')
            ->assertSee('chart-stock-trend')
            ->assertDispatched('charts-updated');
    }

    public function test_stock_trend_switches_unit_and_period(): void
    {
        $user = $this->getLogistikUser();

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->assertSet('trendUnit', 'BTL')
            ->assertSet('trendPeriod', 30)
            ->call('setTrendUnit', 'AMP')
            ->assertSet('trendUnit', 'AMP')
            ->call('setTrendUnit', 'PCS')
            ->assertSet('trendUnit', 'PCS')
            ->call('setTrendUnit', 'INVALID')
            ->assertSet('trendUnit', 'PCS') // Unchanged because invalid
            ->call('setTrendPeriod', 7)
            ->assertSet('trendPeriod', 7)
            ->call('setTrendPeriod', 90)
            ->assertSet('trendPeriod', 90)
            ->call('setTrendPeriod', 15)
            ->assertSet('trendPeriod', 90); // Unchanged because invalid
    }

    public function test_calculate_stock_trend_separates_units_and_returns_null_for_missing_dates(): void
    {
        $distributor = Distributor::create([
            'distributor_code' => 'KFTD-TEST-TREND',
            'name' => 'KFTD Cabang Test Trend',
            'city' => 'Jakarta',
            'province' => 'DKI Jakarta',
            'status' => 'active',
        ]);

        $itemBtl = DistributorItem::create([
            'distributor_id' => $distributor->id,
            'item_code' => 'ITEM-BTL-01',
            'item_name' => 'Produk Cair Botol',
        ]);

        $itemAmp = DistributorItem::create([
            'distributor_id' => $distributor->id,
            'item_code' => 'ITEM-AMP-01',
            'item_name' => 'Produk Injeksi Ampul',
        ]);

        $day1 = '2026-09-01';
        $day2 = '2026-09-02';
        // Day 3 is skipped (missing snapshot / holiday)
        $day4 = '2026-09-04';

        StockEntry::create([
            'distributor_id' => $distributor->id,
            'distributor_item_id' => $itemBtl->id,
            'tanggal' => $day1,
            'satuan' => 'BOTOL',
            'quantity' => 100,
            'batch_no' => 'B001',
        ]);

        StockEntry::create([
            'distributor_id' => $distributor->id,
            'distributor_item_id' => $itemAmp->id,
            'tanggal' => $day1,
            'satuan' => 'AMP',
            'quantity' => 50,
            'batch_no' => 'A001',
        ]);

        StockEntry::create([
            'distributor_id' => $distributor->id,
            'distributor_item_id' => $itemBtl->id,
            'tanggal' => $day2,
            'satuan' => 'BTL',
            'quantity' => 120,
            'batch_no' => 'B002',
        ]);

        StockEntry::create([
            'distributor_id' => $distributor->id,
            'distributor_item_id' => $itemBtl->id,
            'tanggal' => $day4,
            'satuan' => 'BOTOL',
            'quantity' => 150,
            'batch_no' => 'B003',
        ]);

        $component = new Dashboard();
        $component->trendUnit = 'BTL';
        $component->trendPeriod = 4; // 2026-09-01 to 2026-09-04 (4 days)

        $trendResult = $component->calculateStockTrend([$distributor->id], $day4);

        $this->assertEquals('BTL', $trendResult['unit']);
        $this->assertEquals(4, $trendResult['period']);
        $this->assertEquals('2026-09-01', $trendResult['start_date']);
        $this->assertEquals('2026-09-04', $trendResult['end_date']);

        // Data array should have 4 points: [100, 120, null, 150]
        $this->assertCount(4, $trendResult['data']);
        $this->assertEquals(100.0, $trendResult['data'][0]);
        $this->assertEquals(120.0, $trendResult['data'][1]);
        $this->assertNull($trendResult['data'][2], 'Missing snapshot date must return null, not 0');
        $this->assertEquals(150.0, $trendResult['data'][3]);

        // Verify active days & deltas
        $this->assertEquals(3, $trendResult['active_days']);
        $this->assertEquals(100.0, $trendResult['first_qty']);
        $this->assertEquals(150.0, $trendResult['latest_qty']);
        $this->assertEquals(50.0, $trendResult['delta']);
        $this->assertEquals(50.0, $trendResult['delta_pct']);

        // Now test unit AMP on the same dataset
        $component->trendUnit = 'AMP';
        $trendAmp = $component->calculateStockTrend([$distributor->id], $day4);

        $this->assertEquals('AMP', $trendAmp['unit']);
        $this->assertEquals(50.0, $trendAmp['data'][0]);
        $this->assertNull($trendAmp['data'][1]);
        $this->assertNull($trendAmp['data'][2]);
        $this->assertNull($trendAmp['data'][3]);
        $this->assertEquals(1, $trendAmp['active_days']);
    }
}
