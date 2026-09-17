<?php

namespace Tests\Feature;

use App\Livewire\Distributors\Index;
use App\Models\Distributor;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DistributorSenderEmailTest extends TestCase
{
    use DatabaseTransactions;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        (new \Database\Seeders\RolePermissionSeeder)->run();

        $this->adminUser = User::firstOrCreate(
            ['email' => 'admin.test@satoriagroup.co.id'],
            [
                'name' => 'Admin Test',
                'password' => bcrypt('password'),
            ]
        );
        $this->adminUser->syncRoles([User::ROLE_ADMIN]);
    }

    public function test_distributor_page_renders_with_whitelist_email_column_and_banner(): void
    {
        $code = 'TEST_WL_' . uniqid();
        Distributor::create([
            'distributor_code' => $code,
            'name' => 'PT Kimia Farma Bandung ' . $code,
            'sender_email' => 'bandung@kftd.co.id, @kftd.co.id',
            'is_active' => true,
        ]);

        Livewire::actingAs($this->adminUser)
            ->test(Index::class)
            ->assertSee('Whitelist Email Pengirim Otomasi Laporan Stok')
            ->assertSee('Email Whitelist')
            ->set('search', $code)
            ->assertSee('bandung@kftd.co.id')
            ->assertSee('@kftd.co.id');
    }

    public function test_can_create_distributor_with_sender_email(): void
    {
        $code = 'AA_' . uniqid();

        Livewire::actingAs($this->adminUser)
            ->test(Index::class)
            ->call('openCreate')
            ->set('distributor_code', $code)
            ->set('name', 'Anugrah Argon ' . $code)
            ->set('sender_email', 'sby.report@anugrahargon.com, @anugrahargon.com')
            ->set('is_active', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('distributors', [
            'distributor_code' => $code,
            'sender_email' => 'sby.report@anugrahargon.com, @anugrahargon.com',
        ]);
    }

    public function test_validates_invalid_sender_email(): void
    {
        $code = 'INV_' . uniqid();

        Livewire::actingAs($this->adminUser)
            ->test(Index::class)
            ->call('openCreate')
            ->set('distributor_code', $code)
            ->set('name', 'Test Distributor ' . $code)
            ->set('sender_email', 'invalid-email-address')
            ->call('save')
            ->assertHasErrors(['sender_email']);
    }

    public function test_can_update_existing_distributor_sender_email(): void
    {
        $code = 'MPI_' . uniqid();
        $dist = Distributor::create([
            'distributor_code' => $code,
            'name' => 'PT Menjangan Sakti ' . $code,
            'sender_email' => 'old@menjangan.com',
            'is_active' => true,
        ]);

        Livewire::actingAs($this->adminUser)
            ->test(Index::class)
            ->call('openEdit', $dist->id)
            ->assertSet('sender_email', 'old@menjangan.com')
            ->set('sender_email', 'new.reporting@menjangan.com, backup@menjangan.com')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('distributors', [
            'id' => $dist->id,
            'sender_email' => 'new.reporting@menjangan.com, backup@menjangan.com',
        ]);
    }

    public function test_can_search_distributor_by_sender_email(): void
    {
        $uniq = uniqid();
        $emailOne = "unique.finance.{$uniq}@one.com";

        $d1 = Distributor::create([
            'distributor_code' => 'D1_' . $uniq,
            'name' => 'Distributor One ' . $uniq,
            'sender_email' => $emailOne,
            'is_active' => true,
        ]);

        $d2 = Distributor::create([
            'distributor_code' => 'D2_' . $uniq,
            'name' => 'Distributor Two ' . $uniq,
            'sender_email' => "other.{$uniq}@two.com",
            'is_active' => true,
        ]);

        Livewire::actingAs($this->adminUser)
            ->test(Index::class)
            ->set('search', $emailOne)
            ->assertSee($d1->name)
            ->assertDontSee($d2->name);
    }
}
