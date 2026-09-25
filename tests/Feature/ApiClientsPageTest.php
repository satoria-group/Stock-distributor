<?php

namespace Tests\Feature;

use App\Livewire\ApiClients\Index;
use App\Models\ApiClient;
use App\Models\DistributorGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class ApiClientsPageTest extends TestCase
{
    use DatabaseTransactions;

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->syncRoles([$role]);

        return $user;
    }

    public function test_admin_bisa_membuka_halaman_dan_membuat_token(): void
    {
        $admin = $this->userWithRole(User::ROLE_ADMIN);
        $group = DistributorGroup::create(['name' => 'Grup Halaman '.uniqid()]);

        $this->actingAs($admin)->get(route('api-clients.index'))->assertOk()->assertSee('Akses API Distributor');

        $component = Livewire::actingAs($admin)->test(Index::class)
            ->set('name', 'SDL ERP')
            ->set('distributor_group_id', $group->id)
            ->set('allowed_ips', '203.0.113.10, 203.0.113.11')
            ->call('save')
            ->assertHasNoErrors();

        $token = $component->get('plainToken');
        $client = ApiClient::findByToken($token);
        $this->assertNotNull($client);
        $this->assertSame('203.0.113.10,203.0.113.11', $client->allowed_ips);

        $component->call('regenerate', $client->id);
        $this->assertNull(ApiClient::findByToken($token), 'Token lama harus langsung tidak berlaku.');
    }

    public function test_ip_tidak_valid_ditolak(): void
    {
        $group = DistributorGroup::create(['name' => 'Grup IP '.uniqid()]);

        Livewire::actingAs($this->userWithRole(User::ROLE_ADMIN))->test(Index::class)
            ->set('name', 'X')
            ->set('distributor_group_id', $group->id)
            ->set('allowed_ips', 'bukan-ip')
            ->call('save')
            ->assertHasErrors('allowed_ips');
    }

    public function test_selain_admin_tidak_bisa_membuka(): void
    {
        $this->actingAs($this->userWithRole(User::ROLE_LOGISTIK))->get(route('api-clients.index'))->assertForbidden();
    }
}
