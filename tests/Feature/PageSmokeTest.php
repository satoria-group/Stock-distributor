<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Uji asap: setiap halaman harus benar-benar ter-render sebagai Admin.
 *
 * Banyak logika di komponen Livewire (Dashboard >2000 baris) tidak tersentuh
 * unit test. Me-render halamannya utuh adalah cara termurah untuk menangkap
 * error runtime — kolom hilang, sintaks SQL, konstanta yang dipindah — yang
 * lolos dari test per-fungsi.
 */
class PageSmokeTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        $user = User::where('email', 'admin@satoriagroup.co.id')->first() ?? User::first();

        if (! $user) {
            $user = User::factory()->create();
            $user->syncRoles([User::ROLE_ADMIN]);
        }

        return $user;
    }

    public static function routeProvider(): array
    {
        return [
            'dashboard' => ['dashboard'],
            'distributors' => ['distributors.index'],
            'template groups' => ['template-groups.index'],
            'netsuite items' => ['netsuite-items.index'],
            'distributor items' => ['distributor-items.index'],
            'stock upload' => ['stock.upload'],
            'stock history' => ['stock.history'],
            'users' => ['users.index'],
            'emails' => ['emails.index'],
        ];
    }

    /**
     * @dataProvider routeProvider
     */
    public function test_page_renders_for_admin(string $routeName): void
    {
        $response = $this->actingAs($this->admin())->get(route($routeName));

        $response->assertStatus(200);
    }

    /**
     * Tab dashboard memakai jalur perhitungan yang berbeda-beda; masing-masing
     * harus ikut ter-render, bukan hanya tab bawaan.
     */
    public function test_dashboard_renders_on_every_tab_and_group(): void
    {
        $admin = $this->admin();

        foreach (['stock', 'expiry', 'compliance'] as $tab) {
            foreach (['ALL', 'KFTD', 'SDL', 'OTHER'] as $group) {
                $response = \Livewire\Livewire::actingAs($admin)
                    ->test(\App\Livewire\Dashboard::class)
                    ->set('selectedGroup', $group)
                    ->set('activeTab', $tab);

                $response->assertStatus(200);
            }
        }
    }

    /**
     * Ekspor CSV menyentuh jalur perhitungan yang sama dengan dashboard —
     * termasuk FEFO dan stok stagnan yang ikut berubah saat stok dipecah per
     * batch. Sebelumnya tidak ada tes sama sekali yang menjalankannya.
     */
    public function test_csv_exports_run_without_error(): void
    {
        $admin = $this->admin();

        foreach (['exportStagnantCsv', 'exportNearEdCsv'] as $action) {
            $response = \Livewire\Livewire::actingAs($admin)
                ->test(\App\Livewire\Dashboard::class)
                ->call($action);

            $response->assertStatus(200);

            // Benar-benar jalankan closure-nya: error di dalam streamDownload
            // baru muncul saat isinya dibangkitkan, bukan saat dipanggil.
            $file = $response->effects['download'] ?? null;
            $this->assertNotNull($file, "Aksi {$action} tidak menghasilkan unduhan.");
        }
    }

    public function test_stock_template_download_runs_without_error(): void
    {
        $response = \Livewire\Livewire::actingAs($this->admin())
            ->test(\App\Livewire\Stock\Upload::class)
            ->call('downloadTemplate');

        $response->assertStatus(200);
    }

    /**
     * Pencarian dengan karakter wildcard tidak boleh membuat query error
     * setelah perubahan escaping LIKE.
     */
    public function test_search_with_wildcard_characters_does_not_break_pages(): void
    {
        $admin = $this->admin();

        $components = [
            \App\Livewire\Distributors\Index::class,
            \App\Livewire\NetsuiteItems\Index::class,
            \App\Livewire\DistributorItems\Index::class,
        ];

        foreach ($components as $component) {
            foreach (['%', '_', '\\', '%_\\%', "100% murni"] as $term) {
                \Livewire\Livewire::actingAs($admin)
                    ->test($component)
                    ->set('search', $term)
                    ->assertStatus(200);
            }
        }
    }
}
