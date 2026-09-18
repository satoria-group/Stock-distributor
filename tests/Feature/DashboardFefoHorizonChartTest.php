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

class DashboardFefoHorizonChartTest extends TestCase
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

    public function test_fefo_horizon_chart_renders_on_expiry_tab(): void
    {
        $user = $this->getLogistikUser();

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->set('activeTab', 'expiry')
            ->assertSet('fefoChartUnit', 'ALL')
            ->assertSee('Distribusi Horizon Kedaluwarsa Makro')
            ->assertSee('Komposisi Umur Simpan Stok Agregat')
            ->assertSee('Semua Satuan')
            ->assertSee('Botol (BTL)')
            ->assertSee('Ampul (AMP)')
            ->assertSee('Pcs / Box (PCS)')
            ->assertDispatched('charts-updated');
    }

    public function test_fefo_horizon_switches_unit_and_resets(): void
    {
        $user = $this->getLogistikUser();

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->set('activeTab', 'expiry')
            ->assertSet('fefoChartUnit', 'ALL')
            ->call('setFefoChartUnit', 'BTL')
            ->assertSet('fefoChartUnit', 'BTL')
            ->call('setFefoChartUnit', 'AMP')
            ->assertSet('fefoChartUnit', 'AMP')
            ->call('setFefoChartUnit', 'PCS')
            ->assertSet('fefoChartUnit', 'PCS')
            ->call('setFefoChartUnit', 'INVALID')
            ->assertSet('fefoChartUnit', 'PCS') // Unchanged
            ->call('resetFefoFilters')
            ->assertSet('fefoChartUnit', 'ALL'); // Reset back to default ALL
    }

    public function test_calculate_fefo_horizon_categorizes_into_five_zones(): void
    {
        $today = Carbon::today();

        // 1. Distributor KFTD
        $distributor = Distributor::create([
            'distributor_code' => 'KFTD-TEST-FEFO',
            'name' => 'KFTD Test Horizon Branch',
            'is_active' => true,
        ]);

        $item1 = DistributorItem::create([
            'distributor_id' => $distributor->id,
            'source_item_id' => 'ITEM-FEFO-01',
            'item_name' => 'Infus Sodium Chloride 01',
        ]);
        $item2 = DistributorItem::create([
            'distributor_id' => $distributor->id,
            'source_item_id' => 'ITEM-FEFO-02',
            'item_name' => 'Infus Sodium Chloride 02',
        ]);
        $item3 = DistributorItem::create([
            'distributor_id' => $distributor->id,
            'source_item_id' => 'ITEM-FEFO-03',
            'item_name' => 'Infus Sodium Chloride 03',
        ]);
        $item4 = DistributorItem::create([
            'distributor_id' => $distributor->id,
            'source_item_id' => 'ITEM-FEFO-04',
            'item_name' => 'Infus Sodium Chloride 04',
        ]);
        $item5 = DistributorItem::create([
            'distributor_id' => $distributor->id,
            'source_item_id' => 'ITEM-FEFO-05',
            'item_name' => 'Infus Sodium Chloride 05',
        ]);
        $itemAmp = DistributorItem::create([
            'distributor_id' => $distributor->id,
            'source_item_id' => 'ITEM-FEFO-AMP',
            'item_name' => 'Injeksi Ampul Test',
        ]);

        // Buat 5 batch BTL dengan umur simpan di masing-masing 5 zona horizon
        // Zone 1: < 1 Bulan (10 hari)
        StockEntry::create([
            'distributor_id' => $distributor->id,
            'distributor_item_id' => $item1->id,
            'tanggal' => $today->toDateString(),
            'batch_no' => 'BATCH-ZONE1',
            'expired_date' => $today->copy()->addDays(10)->toDateString(),
            'quantity' => 100,
            'satuan' => 'BTL',
        ]);

        // Zone 2: 1 - 3 Bulan (60 hari)
        StockEntry::create([
            'distributor_id' => $distributor->id,
            'distributor_item_id' => $item2->id,
            'tanggal' => $today->toDateString(),
            'batch_no' => 'BATCH-ZONE2',
            'expired_date' => $today->copy()->addDays(60)->toDateString(),
            'quantity' => 200,
            'satuan' => 'BTL',
        ]);

        // Zone 3: 3 - 6 Bulan (120 hari)
        StockEntry::create([
            'distributor_id' => $distributor->id,
            'distributor_item_id' => $item3->id,
            'tanggal' => $today->toDateString(),
            'batch_no' => 'BATCH-ZONE3',
            'expired_date' => $today->copy()->addDays(120)->toDateString(),
            'quantity' => 300,
            'satuan' => 'BOTOL', // Menguji variasi satuan BOTOL
        ]);

        // Zone 4: 6 - 12 Bulan (250 hari)
        StockEntry::create([
            'distributor_id' => $distributor->id,
            'distributor_item_id' => $item4->id,
            'tanggal' => $today->toDateString(),
            'batch_no' => 'BATCH-ZONE4',
            'expired_date' => $today->copy()->addDays(250)->toDateString(),
            'quantity' => 400,
            'satuan' => 'BTL',
        ]);

        // Zone 5: > 12 Bulan (400 hari)
        StockEntry::create([
            'distributor_id' => $distributor->id,
            'distributor_item_id' => $item5->id,
            'tanggal' => $today->toDateString(),
            'batch_no' => 'BATCH-ZONE5',
            'expired_date' => $today->copy()->addDays(400)->toDateString(),
            'quantity' => 500,
            'satuan' => 'BTL',
        ]);

        // Batch AMP: harus diabaikan saat unit BTL dipilih
        StockEntry::create([
            'distributor_id' => $distributor->id,
            'distributor_item_id' => $itemAmp->id,
            'tanggal' => $today->toDateString(),
            'batch_no' => 'BATCH-AMP-ONLY',
            'expired_date' => $today->copy()->addDays(60)->toDateString(),
            'quantity' => 9999,
            'satuan' => 'AMP',
        ]);

        $dashboard = new Dashboard();
        $dashboard->fefoChartUnit = 'BTL';
        $dashboard->fefoBranchId = $distributor->id;

        $entries = StockEntry::with(['distributor', 'distributorItem.netsuiteItem'])
            ->where('distributor_id', $distributor->id)
            ->where('tanggal', $today->toDateString())
            ->get();

        $horizon = $dashboard->calculateFefoHorizon($entries, [$distributor->id]);

        $this->assertEquals('BTL', $horizon['unit']);
        $this->assertEquals('Botol (BTL)', $horizon['unit_label']);
        $this->assertEquals(1500, $horizon['total_qty']); // 100+200+300+400+500 (AMP tidak tercampur)
        $this->assertEquals(5, $horizon['total_batches']);
        $this->assertTrue($horizon['has_data']);

        // Verifikasi persentase nasional / agregat
        $this->assertEquals(100, $horizon['national']['expired']['qty']);
        $this->assertEquals(6.7, $horizon['national']['expired']['pct']); // 100/1500 = 6.67% => 6.7%

        $this->assertEquals(200, $horizon['national']['critical']['qty']);
        $this->assertEquals(13.3, $horizon['national']['critical']['pct']); // 200/1500 = 13.33% => 13.3%

        $this->assertEquals(300, $horizon['national']['warning']['qty']);
        $this->assertEquals(20.0, $horizon['national']['warning']['pct']); // 300/1500 = 20.0%

        $this->assertEquals(400, $horizon['national']['caution']['qty']);
        $this->assertEquals(26.7, $horizon['national']['caution']['pct']); // 400/1500 = 26.67% => 26.7%

        $this->assertEquals(500, $horizon['national']['safe']['qty']);
        $this->assertEquals(33.3, $horizon['national']['safe']['pct']); // 500/1500 = 33.33% => 33.3%

        // Verifikasi datasets untuk Chart.js (5 datasets)
        $this->assertCount(5, $horizon['datasets']);
        $this->assertEquals('< 1 Bulan / Expired', $horizon['datasets'][0]['label']);
        $this->assertEquals('#dc2626', $horizon['datasets'][0]['backgroundColor']);
        $this->assertEquals([100], $horizon['datasets'][0]['data']);

        $this->assertEquals('1 - 3 Bulan (< 90 Hari)', $horizon['datasets'][1]['label']);
        $this->assertEquals('#e11d48', $horizon['datasets'][1]['backgroundColor']);
        $this->assertEquals([200], $horizon['datasets'][1]['data']);

        $this->assertEquals('3 - 6 Bulan (90 - 180 Hari)', $horizon['datasets'][2]['label']);
        $this->assertEquals('#f59e0b', $horizon['datasets'][2]['backgroundColor']);
        $this->assertEquals([300], $horizon['datasets'][2]['data']);

        $this->assertEquals('6 - 12 Bulan (180 - 365 Hari)', $horizon['datasets'][3]['label']);
        $this->assertEquals('#06b6d4', $horizon['datasets'][3]['backgroundColor']);
        $this->assertEquals([400], $horizon['datasets'][3]['data']);

        $this->assertEquals('> 12 Bulan (> 365 Hari)', $horizon['datasets'][4]['label']);
        $this->assertEquals('#10b981', $horizon['datasets'][4]['backgroundColor']);
        $this->assertEquals([500], $horizon['datasets'][4]['data']);
    }

    public function test_fefo_horizon_view_data_and_dispatch(): void
    {
        $user = $this->getLogistikUser();

        $comp = Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->set('activeTab', 'expiry');

        $comp->assertDispatched('charts-updated');

        $horizon = $comp->viewData('chartFefoHorizon');
        $this->assertIsArray($horizon);
        $this->assertArrayHasKey('labels', $horizon);
        $this->assertArrayHasKey('datasets', $horizon);
        $this->assertArrayHasKey('national', $horizon);
        $this->assertArrayHasKey('unit', $horizon);
        $this->assertArrayHasKey('unit_label', $horizon);
        $this->assertEquals('ALL', $horizon['unit']);
        $this->assertEquals('Semua Satuan', $horizon['unit_label']);
        $this->assertCount(5, $horizon['datasets']);
    }

    public function test_fefo_horizon_with_all_units_aggregates_all_satuan(): void
    {
        $today = Carbon::today();

        $distributor = Distributor::create([
            'distributor_code' => 'TEST-FEFO-ALL',
            'name' => 'Test FEFO All Units',
            'is_active' => true,
        ]);

        $itemBtl = DistributorItem::create([
            'distributor_id' => $distributor->id,
            'source_item_id' => 'ITEM-BTL',
            'item_name' => 'Produk Botol',
        ]);
        $itemAmp = DistributorItem::create([
            'distributor_id' => $distributor->id,
            'source_item_id' => 'ITEM-AMP',
            'item_name' => 'Produk Ampul',
        ]);

        StockEntry::create([
            'distributor_id' => $distributor->id,
            'distributor_item_id' => $itemBtl->id,
            'tanggal' => $today->toDateString(),
            'batch_no' => 'BATCH-BTL',
            'expired_date' => $today->copy()->addDays(60)->toDateString(),
            'quantity' => 100,
            'satuan' => 'BTL',
        ]);

        StockEntry::create([
            'distributor_id' => $distributor->id,
            'distributor_item_id' => $itemAmp->id,
            'tanggal' => $today->toDateString(),
            'batch_no' => 'BATCH-AMP',
            'expired_date' => $today->copy()->addDays(60)->toDateString(),
            'quantity' => 250,
            'satuan' => 'AMP',
        ]);

        $dashboard = new Dashboard();
        $dashboard->fefoChartUnit = 'ALL';
        $dashboard->fefoSatuanFilter = '';
        $dashboard->fefoBranchId = $distributor->id;

        $entries = StockEntry::with(['distributor', 'distributorItem.netsuiteItem'])
            ->where('distributor_id', $distributor->id)
            ->where('tanggal', $today->toDateString())
            ->get();

        $horizon = $dashboard->calculateFefoHorizon($entries, [$distributor->id]);

        $this->assertEquals('ALL', $horizon['unit']);
        $this->assertEquals('Semua Satuan', $horizon['unit_label']);
        // Harus menggabungkan BTL (100) dan AMP (250) = 350
        $this->assertEquals(350, $horizon['total_qty']);
        $this->assertEquals(2, $horizon['total_batches']);
    }
}
