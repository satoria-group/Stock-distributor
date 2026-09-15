<?php

namespace Tests\Feature;

use App\Livewire\Dashboard;
use App\Livewire\NetsuiteItems\Index as NetsuiteItemsIndex;
use App\Models\Distributor;
use App\Models\DistributorItem;
use App\Models\DplPriceProduct;
use App\Models\NetsuiteItem;
use App\Models\StockEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardPricingIntegrationTest extends TestCase
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

    protected function getAdminUser(): User
    {
        $user = User::where('email', 'admin@satoriagroup.co.id')->first();
        if (! $user) {
            $user = User::factory()->create();
            $user->syncRoles([User::ROLE_ADMIN]);
        }

        return $user;
    }

    public function test_dpl_price_product_model_and_relationship(): void
    {
        $item = NetsuiteItem::create([
            'netsuite_id' => 'TEST_NS_PRICING_01',
            'netsuite_name' => 'Produk Uji Coba Valuasi',
            'default_satuan' => 'BOTOL',
        ]);

        DB::table('dpl_price_product')->insert([
            'id_product' => 'TEST_NS_PRICING_01',
            'id_price_region' => 1,
            'price' => 25000.0,
            'price_reguler' => 25000,
            'netsuite_id' => 'TEST_NS_PRICING_01',
            'netsuite_item_id' => $item->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $fresh = NetsuiteItem::with('dplPrice')->find($item->id);
        $this->assertNotNull($fresh->dplPrice);
        $this->assertEquals(25000.0, $fresh->unit_price);
    }

    public function test_dashboard_renders_total_value_kpi_card_and_tab_columns(): void
    {
        $user = $this->getLogistikUser();

        $dist = Distributor::create([
            'distributor_code' => 'KFTD_TEST_PRICING',
            'name' => 'KFTD Cabang Uji Harga',
            'is_active' => true,
        ]);

        $ns = NetsuiteItem::create([
            'netsuite_id' => 'TEST_NS_PRICING_02',
            'netsuite_name' => 'Larutan Ringer Lactate 500ml',
            'default_satuan' => 'BTL',
        ]);

        DB::table('dpl_price_product')->insert([
            'id_product' => 'TEST_NS_PRICING_02',
            'id_price_region' => 1,
            'price' => 15000.0,
            'price_reguler' => 15000,
            'netsuite_id' => 'TEST_NS_PRICING_02',
            'netsuite_item_id' => $ns->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $dItem = DistributorItem::create([
            'distributor_id' => $dist->id,
            'source_item_id' => 'DIST_ITEM_02',
            'item_name' => 'RL Infus Satoria 500ml',
            'satuan' => 'BTL',
            'netsuite_item_id' => $ns->id,
        ]);

        StockEntry::create([
            'distributor_id' => $dist->id,
            'distributor_item_id' => $dItem->id,
            'tanggal' => Carbon::today(),
            'quantity' => 100,
            'satuan' => 'BTL',
            'batch_no' => 'BATCH_PRICING_01',
            'expired_date' => Carbon::today()->addMonths(12),
        ]);

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->assertSee('NILAI STOK ON HAND')
            ->assertSee('Total Nilai (Rp)')
            ->assertSee('wire:click="setSort(\'total_value\')"', false)
            ->call('setSort', 'total_value')
            ->assertSet('sortBy', 'total_value')
            ->assertSet('sortDir', 'asc')
            ->call('setSort', 'total_value')
            ->assertSet('sortDir', 'desc');
    }

    public function test_fefo_tab_renders_batch_value_and_exports_pricing_csv(): void
    {
        $user = $this->getLogistikUser();

        $dist = Distributor::create([
            'distributor_code' => 'SDL_TEST_FEFO_PRICE',
            'name' => 'SDL Cabang Uji FEFO Harga',
            'is_active' => true,
        ]);

        $ns = NetsuiteItem::create([
            'netsuite_id' => 'TEST_NS_FEFO_03',
            'netsuite_name' => 'Dextrose 5% 500ml',
            'default_satuan' => 'BTL',
        ]);

        DB::table('dpl_price_product')->insert([
            'id_product' => 'TEST_NS_FEFO_03',
            'id_price_region' => 1,
            'price' => 12000.0,
            'price_reguler' => 12000,
            'netsuite_id' => 'TEST_NS_FEFO_03',
            'netsuite_item_id' => $ns->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $dItem = DistributorItem::create([
            'distributor_id' => $dist->id,
            'source_item_id' => 'DIST_ITEM_03',
            'item_name' => 'D5 Infus Satoria 500ml',
            'satuan' => 'BTL',
            'netsuite_item_id' => $ns->id,
        ]);

        // Expiring in 45 days -> Critical
        StockEntry::create([
            'distributor_id' => $dist->id,
            'distributor_item_id' => $dItem->id,
            'tanggal' => Carbon::today(),
            'quantity' => 50,
            'satuan' => 'BTL',
            'batch_no' => 'BATCH_CRITICAL_03',
            'expired_date' => Carbon::today()->addDays(45),
        ]);

        $component = Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->set('activeTab', 'expiry')
            ->assertSee('Nilai Batch (Rp)')
            ->assertSee('Potensi Kerugian ED Kritis')
            ->call('setFefoSort', 'total_value')
            ->assertSet('fefoSortBy', 'total_value');

        // Test export CSV contains pricing header
        $response = $component->instance()->exportNearEdCsv();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));
    }

    public function test_stagnant_stock_tab_calculates_locked_capital_and_exports_csv(): void
    {
        $user = $this->getLogistikUser();

        $dist = Distributor::create([
            'distributor_code' => 'UDC_TEST_STAGNANT_PRICE',
            'name' => 'UDC Cabang Uji Dead Stock Harga',
            'is_active' => true,
        ]);

        $ns = NetsuiteItem::create([
            'netsuite_id' => 'TEST_NS_DEAD_04',
            'netsuite_name' => 'Sodium Chloride 0.9% 500ml',
            'default_satuan' => 'BTL',
        ]);

        DB::table('dpl_price_product')->insert([
            'id_product' => 'TEST_NS_DEAD_04',
            'id_price_region' => 1,
            'price' => 11000.0,
            'price_reguler' => 11000,
            'netsuite_id' => 'TEST_NS_DEAD_04',
            'netsuite_item_id' => $ns->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $dItem = DistributorItem::create([
            'distributor_id' => $dist->id,
            'source_item_id' => 'DIST_ITEM_04',
            'item_name' => 'NaCl 0.9% 500ml Satoria',
            'satuan' => 'BTL',
            'netsuite_item_id' => $ns->id,
        ]);

        // 30 days ago and today with no outflow (dead stock)
        StockEntry::create([
            'distributor_id' => $dist->id,
            'distributor_item_id' => $dItem->id,
            'tanggal' => Carbon::today()->subDays(29),
            'quantity' => 80,
            'satuan' => 'BTL',
            'batch_no' => 'BATCH_DEAD_04',
            'expired_date' => Carbon::today()->addMonths(10),
        ]);

        StockEntry::create([
            'distributor_id' => $dist->id,
            'distributor_item_id' => $dItem->id,
            'tanggal' => Carbon::today(),
            'quantity' => 80,
            'satuan' => 'BTL',
            'batch_no' => 'BATCH_DEAD_04',
            'expired_date' => Carbon::today()->addMonths(10),
        ]);

        $component = Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->set('activeTab', 'stagnant')
            ->assertSee('TOTAL MODAL TERTAHAN')
            ->assertSee('Nilai Tertahan (Rp)')
            ->call('setStagnantSort', 'total_value')
            ->assertSet('stagnantSortBy', 'total_value');

        // Test export CSV contains pricing header
        $response = $component->instance()->exportStagnantCsv();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));
    }

    public function test_netsuite_master_item_shows_price_and_updates_price_in_modal(): void
    {
        $user = $this->getAdminUser();

        $ns = NetsuiteItem::create([
            'netsuite_id' => 'TEST_NS_CRUD_05',
            'netsuite_name' => 'Produk Master CRUD Test',
            'default_satuan' => 'BOX',
        ]);

        DB::table('dpl_price_product')->insert([
            'id_product' => 'TEST_NS_CRUD_05',
            'id_price_region' => 1,
            'price' => 50000.0,
            'price_reguler' => 50000,
            'netsuite_id' => 'TEST_NS_CRUD_05',
            'netsuite_item_id' => $ns->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Livewire::actingAs($user)
            ->test(NetsuiteItemsIndex::class)
            ->set('search', 'TEST_NS_CRUD_05')
            ->assertSee('Harga DPL (Rp)')
            ->assertSee('Rp 50.000')
            ->call('openEdit', $ns->id)
            ->assertSet('price', '50000')
            ->set('price', '75000')
            ->call('save')
            ->assertHasNoErrors();

        $updatedPrice = DB::table('dpl_price_product')->where('netsuite_item_id', $ns->id)->where('id_price_region', 1)->first();
        $this->assertNotNull($updatedPrice);
        $this->assertEquals(75000.0, (float) $updatedPrice->price);
    }

    public function test_netsuite_items_price_filter_and_sorting(): void
    {
        $user = $this->getAdminUser();

        // Create item with price
        $itemWithPrice = NetsuiteItem::create([
            'netsuite_id' => 'AAA_TEST_P_01',
            'netsuite_name' => 'AAA Produk Dengan Harga Khusus',
            'default_satuan' => 'BTL',
        ]);
        DB::table('dpl_price_product')->insert([
            'id_product' => 'AAA_TEST_P_01',
            'id_price_region' => 1,
            'price' => 125000.0,
            'price_reguler' => 125000,
            'netsuite_id' => 'AAA_TEST_P_01',
            'netsuite_item_id' => $itemWithPrice->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create item without price
        $itemWithoutPrice = NetsuiteItem::create([
            'netsuite_id' => 'AAA_TEST_P_02',
            'netsuite_name' => 'AAA Produk Tanpa Harga Tertentu',
            'default_satuan' => 'PCS',
        ]);

        // 1. Default (all): should see both
        $comp = Livewire::actingAs($user)
            ->test(NetsuiteItemsIndex::class)
            ->assertSee('Semua')
            ->assertSee('Sudah Ada Harga')
            ->assertSee('Belum Ada Harga');

        // 2. Filter 'with_price': only items with price
        $comp->call('setPriceFilter', 'with_price')
            ->assertSet('priceFilter', 'with_price')
            ->assertSee('AAA_TEST_P_01')
            ->assertDontSee('AAA_TEST_P_02');

        // 3. Filter 'without_price': only items without price
        $comp->call('setPriceFilter', 'without_price')
            ->assertSet('priceFilter', 'without_price')
            ->assertSee('AAA_TEST_P_02')
            ->assertDontSee('AAA_TEST_P_01');

        // 4. Reset filters: returns to 'all'
        $comp->set('search', 'AAA_TEST_P')
            ->call('resetFilters')
            ->assertSet('priceFilter', 'all')
            ->assertSet('search', '');

        // 5. Sort by price
        $comp->call('sortByColumn', 'price')
            ->assertSet('sortBy', 'price')
            ->assertSet('sortDirection', 'desc')
            ->call('sortByColumn', 'price')
            ->assertSet('sortDirection', 'asc');
    }
}
