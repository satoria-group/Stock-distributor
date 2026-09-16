<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EmailReaderFeatureTest extends TestCase
{
    public function test_guests_are_redirected_from_emails_to_login(): void
    {
        $response = $this->get(route('emails.index'));

        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_email_inbox_page(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'admin@satoriagroup.co.id'],
            ['name' => 'Admin Satoria', 'password' => bcrypt('password')]
        );
        $user->syncRoles([User::ROLE_ADMIN]);

        $response = $this->actingAs($user)->get(route('emails.index'));

        $response->assertStatus(200);
        $response->assertSee('Inbox Email Distributor');
    }

    public function test_unconfigured_imap_displays_friendly_setup_alert(): void
    {
        config([
            'imap.host' => null,
            'imap.username' => null,
        ]);

        $user = User::firstOrCreate(
            ['email' => 'admin@satoriagroup.co.id'],
            ['name' => 'Admin Satoria', 'password' => bcrypt('password')]
        );
        $user->syncRoles([User::ROLE_ADMIN]);

        $response = $this->actingAs($user)->get(route('emails.index'));

        $response->assertStatus(200);
        $response->assertSee('Koneksi Mail Server IMAP Belum Dikonfigurasi');
    }

    public function test_matches_satoria_daily_stock_pattern_and_custom_keywords(): void
    {
        $service = app(\App\Services\ImapService::class);

        $this->assertTrue($service->isDailyStockSubject('Satoria Daily Stock'));
        $this->assertTrue($service->isDailyStockSubject('Laporan Satoria Daily Stock - PT Sehat'));
        $this->assertTrue($service->isDailyStockSubject('[DISTRIBUTOR A] satoria daily stock 12/09/2026'));
        $this->assertFalse($service->isDailyStockSubject('Welcome to GitLab!'));
        $this->assertFalse($service->isDailyStockSubject('Invoice #10293'));
    }

    public function test_can_toggle_only_daily_stock_filter_in_livewire(): void
    {
        $user = User::where('email', 'admin@satoriagroup.co.id')->first() ?? User::first();
        if (! $user) {
            $user = User::factory()->create();
            $user->syncRoles([User::ROLE_ADMIN]);
        }

        Livewire::actingAs($user)
            ->test(\App\Livewire\Emails\Index::class)
            ->assertSet('onlyDailyStock', false)
            ->set('onlyDailyStock', true)
            ->assertSet('onlyDailyStock', true);
    }
}

