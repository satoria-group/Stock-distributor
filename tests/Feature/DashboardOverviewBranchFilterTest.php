<?php

namespace Tests\Feature;

use App\Livewire\Dashboard;
use App\Models\Distributor;
use App\Models\DistributorItem;
use App\Models\NetsuiteItem;
use App\Models\StockEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

/** Filter cabang pada ringkasan atas (KPI + dua grafik utama). */
class DashboardOverviewBranchFilterTest extends TestCase
{
    use DatabaseTransactions;

    /** @return array{0: Distributor, 1: Distributor} */
    private function seedTwoBranches(): array
    {
        $suffix = strtoupper(substr(uniqid(), -6));
        $ns = NetsuiteItem::create(['netsuite_id' => 'NS-OV-'.$suffix, 'netsuite_name' => 'Produk Ringkasan '.$suffix, 'default_satuan' => 'BTL']);

        $branches = [];
        foreach (['A' => 111, 'B' => 222] as $label => $qty) {
            $d = Distributor::create(['distributor_code' => "OV{$label}{$suffix}", 'name' => "Cabang Ringkasan {$label} {$suffix}", 'is_active' => true]);
            $item = DistributorItem::create(['distributor_id' => $d->id, 'item_name' => "ITEM {$label}", 'netsuite_item_id' => $ns->id]);
            StockEntry::create(['tanggal' => '2026-09-24', 'distributor_id' => $d->id, 'distributor_item_id' => $item->id, 'quantity' => $qty, 'satuan' => 'BTL', 'batch_no' => 'B1']);
            $branches[] = $d;
        }

        return $branches;
    }

    private function user(): User
    {
        $user = User::factory()->create();
        $user->syncRoles([User::ROLE_ADMIN]);

        return $user;
    }

    public function test_filter_cabang_menyaring_kpi_dan_grafik_tanpa_menyaring_tabel(): void
    {
        [$a] = $this->seedTwoBranches();

        $component = Livewire::actingAs($this->user())
            ->test(Dashboard::class)
            // Metrik fisik: produk uji tidak punya harga DPL, jadi nilai Rp-nya 0.
            ->call('setDonutMetric', 'qty')
            ->set('overviewBranchId', $a->id);

        $kpi = $component->viewData('kpi');
        $this->assertEquals(111, $kpi['total_all']);
        $this->assertEquals(111, $kpi['total_btl']);
        $this->assertSame(1, $kpi['total_sku']);

        // Satu cabang: grafik Top 10 jadi satu batang, donut jadi komposisi sediaan.
        $this->assertCount(1, $component->viewData('chartTopProducts')['datasets']);
        $this->assertContains('Botol (Btl)', $component->viewData('chartDonut')['labels']);

        // Filter tabel tetap terpisah.
        $component->assertSet('selectedBranchId', null);
    }

    public function test_ganti_grup_mereset_filter_cabang(): void
    {
        [$a] = $this->seedTwoBranches();

        Livewire::actingAs($this->user())
            ->test(Dashboard::class)
            ->set('overviewBranchId', $a->id)
            ->call('setGroup', 'KFTD')
            ->assertSet('overviewBranchId', null);
    }
}
