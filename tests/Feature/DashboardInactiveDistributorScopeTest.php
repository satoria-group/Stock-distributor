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

/**
 * Tab "ALL" harus memakai definisi scope yang sama dengan tab per-grup:
 * hanya distributor AKTIF. Sebelumnya 'ALL' melewati filter sepenuhnya,
 * sehingga stok distributor non-aktif ikut terhitung di KPI — dan
 * menjumlahkan seluruh tab grup tidak pernah sama dengan angka tab ALL.
 */
class DashboardInactiveDistributorScopeTest extends TestCase
{
    use DatabaseTransactions;

    public function test_all_tab_excludes_stock_of_inactive_distributors(): void
    {
        $user = User::where('email', 'admin@satoriagroup.co.id')->first() ?? User::first();
        if (! $user) {
            $user = User::factory()->create();
            $user->syncRoles([User::ROLE_ADMIN]);
        }

        $suffix = strtoupper(substr(md5(uniqid()), 0, 6));
        $tanggal = '2026-09-25';

        $inactive = Distributor::create([
            'distributor_code' => 'ZZINACT'.$suffix,
            'name' => 'Distributor Non Aktif '.$suffix,
            'is_active' => false,
        ]);

        $ns = NetsuiteItem::create([
            'netsuite_id' => 'NS_'.uniqid(),
            'netsuite_name' => 'Item Scope Uji',
        ]);

        $item = DistributorItem::create([
            'distributor_id' => $inactive->id,
            'item_name' => 'Item Distributor Non Aktif '.$suffix,
            'satuan' => 'BTL',
            'netsuite_item_id' => $ns->id,
        ]);

        StockEntry::create([
            'tanggal' => $tanggal,
            'distributor_id' => $inactive->id,
            'distributor_item_id' => $item->id,
            'quantity' => 777777,
            'satuan' => 'BTL',
        ]);

        $component = Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->set('selectedGroup', 'ALL');

        $reflection = new \ReflectionMethod(Dashboard::class, 'latestSnapshotPerDistributor');
        $reflection->setAccessible(true);

        // Scope distributor aktif (tidak memuat si non-aktif).
        $activeIds = Distributor::where('is_active', true)->pluck('id')->all();

        $latest = $reflection->invoke($component->instance(), $activeIds);

        $this->assertNotContains(
            $inactive->id,
            $latest->pluck('distributor_id')->all(),
            'Snapshot distributor NON-AKTIF masih ikut terhitung pada tab ALL.'
        );
    }
}
