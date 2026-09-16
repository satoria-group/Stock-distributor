<?php

namespace Tests\Feature;

use App\Livewire\DistributorItems\Index;
use App\Models\Distributor;
use App\Models\DistributorItem;
use App\Models\NetsuiteItem;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class DistributorItemSearchTest extends TestCase
{
    use DatabaseTransactions;

    public function test_can_search_by_distributor_item_name_or_netsuite_name_or_id(): void
    {
        $admin = User::firstOrCreate(['email' => 'admin_test@satoria.com'], [
            'name' => 'Admin Test',
            'password' => bcrypt('password123'),
        ]);
        $admin->syncRoles([User::ROLE_ADMIN]);

        $dist = Distributor::create([
            'distributor_code' => 'D_SRCH_' . uniqid(),
            'name' => 'Distributor Search Test',
            'is_active' => true,
        ]);

        $ns = NetsuiteItem::create([
            'netsuite_id' => 'NS_CODE_' . uniqid(),
            'netsuite_name' => 'Special NetSuite Medicine XYZ',
        ]);

        $item = DistributorItem::create([
            'distributor_id' => $dist->id,
            'item_name' => 'Dist Unique Local Item ABC',
            'satuan' => 'BTL',
            'netsuite_item_id' => $ns->id,
        ]);

        // Search by item_name
        Livewire::actingAs($admin)
            ->test(Index::class)
            ->set('search', 'Local Item ABC')
            ->assertSee('Dist Unique Local Item ABC');

        // Search by netsuite_name
        Livewire::actingAs($admin)
            ->test(Index::class)
            ->set('search', 'Medicine XYZ')
            ->assertSee('Dist Unique Local Item ABC');

        // Search by netsuite_id
        Livewire::actingAs($admin)
            ->test(Index::class)
            ->set('search', $ns->netsuite_id)
            ->assertSee('Dist Unique Local Item ABC');
    }
}
