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
        $user = User::where('email', 'admin@satoriagroup.co.id')->first() ?? User::first();
        if (! $user) {
            $user = User::factory()->create();
            $user->syncRoles([User::ROLE_ADMIN]);
        }

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

        $user = User::where('email', 'admin@satoriagroup.co.id')->first() ?? User::first();
        if (! $user) {
            $user = User::factory()->create();
            $user->syncRoles([User::ROLE_ADMIN]);
        }

        $response = $this->actingAs($user)->get(route('emails.index'));

        $response->assertStatus(200);
        $response->assertSee('Koneksi Mail Server IMAP Belum Dikonfigurasi');
    }
}
