<?php

namespace Tests\Feature;

use App\Livewire\Roles\Index as RolesIndex;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->syncRoles([$role]);

        return $user;
    }

    public function test_admin_bisa_memberi_akses_halaman_ke_sales(): void
    {
        $sales = $this->userWithRole(User::ROLE_SALES);
        $url = route('netsuite-items.index', ['tab' => 'conversions']);
        $this->actingAs($sales)->get($url)->assertForbidden();

        Livewire::actingAs($this->userWithRole(User::ROLE_ADMIN))
            ->test(RolesIndex::class)
            ->set('matrix.sales.'.RolesIndex::key('unit-conversions.view'), true)
            ->call('save');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        // Sales hanya punya akses Konversi Satuan: halaman terbuka di tab itu
        // tanpa tab Produk.
        $this->actingAs($sales->fresh())->get($url)
            ->assertOk()
            ->assertSee('Saat berkas diimpor')
            ->assertDontSee('Tambah Konversi')
            ->assertDontSee('Tambah Produk');
    }

    public function test_kelola_otomatis_mencentang_lihat(): void
    {
        Livewire::actingAs($this->userWithRole(User::ROLE_ADMIN))
            ->test(RolesIndex::class)
            ->set('matrix.sales.'.RolesIndex::key('netsuite-items.manage'), true)
            ->assertSet('matrix.sales.'.RolesIndex::key('netsuite-items.view'), true);
    }

    public function test_non_admin_tidak_bisa_membuka_hak_akses(): void
    {
        $this->actingAs($this->userWithRole(User::ROLE_LOGISTIK))
            ->get(route('users.index', ['tab' => 'roles']))
            ->assertForbidden();
    }

    public function test_halaman_awal_mengikuti_akses_role(): void
    {
        // Logistik tanpa akses dashboard diarahkan ke halaman lain yang boleh dibuka.
        $role = \Spatie\Permission\Models\Role::findByName(User::ROLE_LOGISTIK, 'web');
        $role->revokePermissionTo('dashboard.view');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->actingAs($this->userWithRole(User::ROLE_LOGISTIK))
            ->get('/')
            ->assertRedirect(route('template-groups.index'));
    }
}
