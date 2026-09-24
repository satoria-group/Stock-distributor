<?php

namespace Tests\Feature;

use App\Livewire\Dashboard;
use App\Livewire\Distributors\Index as Distributors;
use App\Models\Distributor;
use App\Models\DistributorGroup;
use App\Models\DistributorTemplateGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Pengelompokan distributor kini DIDEFINISIKAN, bukan ditebak dari awalan kode.
 *
 * Bentuk berkas Excel ikut menempel pada grup usaha, sehingga satu kali setelan
 * berlaku untuk seluruh cabangnya — dengan kolom di cabang sebagai pengecualian.
 */
class DistributorGroupTest extends TestCase
{
    use DatabaseTransactions;

    private string $suffix;

    protected function setUp(): void
    {
        parent::setUp();
        $this->suffix = strtoupper(substr(uniqid(), -6));
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->syncRoles([User::ROLE_ADMIN]);

        return $user;
    }

    private function distributor(string $code, ?DistributorGroup $group = null): Distributor
    {
        return Distributor::create([
            'distributor_code' => $code,
            'name' => 'Distributor '.$code,
            'is_active' => true,
            'distributor_group_id' => $group?->id,
        ]);
    }

    public function test_kode_tidak_lagi_menentukan_grup(): void
    {
        $group = DistributorGroup::create(['name' => 'Grup '.$this->suffix, 'color' => '#3b82f6']);

        // Kodenya sama sekali tidak menyerupai nama grupnya — dulu kasus seperti
        // ini (kode IGM untuk grup GMP) selalu jatuh ke keranjang 'Lainnya'.
        $anggota = $this->distributor('ZZZ'.$this->suffix, $group);
        $bukanAnggota = $this->distributor('YYY'.$this->suffix);

        $this->assertSame('Grup '.$this->suffix, Dashboard::getDistributorGroup($anggota->fresh()));
        $this->assertSame('OTHER', Dashboard::getDistributorGroup($bukanAnggota->fresh()));
    }

    public function test_grup_nonaktif_anggotanya_terhitung_lainnya(): void
    {
        $group = DistributorGroup::create(['name' => 'Mati '.$this->suffix, 'is_active' => false]);
        $anggota = $this->distributor('QQQ'.$this->suffix, $group);

        // Angkanya tidak boleh hilang hanya karena grupnya disembunyikan.
        $this->assertSame('OTHER', Dashboard::getDistributorGroup($anggota->fresh()));
        $this->assertNotContains('Mati '.$this->suffix, Dashboard::groupKeys());
    }

    public function test_bentuk_berkas_diwarisi_dari_grup_dan_bisa_ditimpa(): void
    {
        $template = DistributorTemplateGroup::create(['name' => 'Format '.$this->suffix]);
        $lain = DistributorTemplateGroup::create(['name' => 'Format Lain '.$this->suffix]);

        $group = DistributorGroup::create([
            'name' => 'Warisan '.$this->suffix,
            'template_group_id' => $template->id,
        ]);

        $ikut = $this->distributor('AAA'.$this->suffix, $group);
        $this->assertSame($template->id, $ikut->fresh()->effectiveTemplateGroup()?->id);
        $this->assertTrue($ikut->fresh()->templateIsInherited());

        // Cabang yang formatnya menyimpang menimpa warisan grupnya.
        $menyimpang = $this->distributor('BBB'.$this->suffix, $group);
        $menyimpang->update(['template_group_id' => $lain->id]);

        $this->assertSame($lain->id, $menyimpang->fresh()->effectiveTemplateGroup()?->id);
        $this->assertFalse($menyimpang->fresh()->templateIsInherited());
    }

    public function test_penetapan_massal_memindahkan_banyak_distributor_sekaligus(): void
    {
        $group = DistributorGroup::create(['name' => 'Massal '.$this->suffix]);
        $a = $this->distributor('CCC'.$this->suffix);
        $b = $this->distributor('DDD'.$this->suffix);

        Livewire::actingAs($this->admin())
            ->test(Distributors::class)
            ->set('selected', [$a->id, $b->id])
            ->set('bulkGroupId', $group->id)
            ->call('assignSelectedToGroup');

        $this->assertSame($group->id, $a->fresh()->distributor_group_id);
        $this->assertSame($group->id, $b->fresh()->distributor_group_id);
    }

    public function test_usulan_grup_hanya_untuk_awalan_yang_bercabang(): void
    {
        // Kode sungguhan berupa huruf; asisten memang hanya membaca bagian
        // hurufnya, jadi angka pada kode uji akan memotong awalannya.
        $prefix = 'UJ'.strtr(substr($this->suffix, 0, 2), '0123456789', 'ABCDEFGHIJ');

        $this->distributor($prefix.'BANDUNG');
        $this->distributor($prefix.'MEDAN');
        // Berdiri sendiri: tidak boleh diusulkan jadi grup.
        $this->distributor('SOLO'.$this->suffix);

        $component = Livewire::actingAs($this->admin())
            ->test(Distributors::class)
            ->call('suggestGroups');

        $prefixes = array_column($component->get('suggestions'), 'prefix');

        $this->assertContains($prefix, $prefixes);
        $this->assertNotContains('SOLO', $prefixes);

        // Menyetujui usulan membuat grupnya sekaligus mengisi anggotanya.
        $component->call('applySuggestion', $prefix);

        $group = DistributorGroup::where('name', $prefix)->first();
        $this->assertNotNull($group);
        $this->assertSame(2, $group->distributors()->count());
    }

    public function test_menghapus_grup_tidak_menghapus_distributornya(): void
    {
        $group = DistributorGroup::create(['name' => 'Hapus '.$this->suffix]);
        $anggota = $this->distributor('EEE'.$this->suffix, $group);

        Livewire::actingAs($this->admin())
            ->test(Distributors::class)
            ->call('deleteGroup', $group->id);

        $this->assertNotNull($anggota->fresh());
        $this->assertNull($anggota->fresh()->distributor_group_id);
    }

    public function test_status_aktif_bisa_diubah_per_baris_dan_massal(): void
    {
        $a = $this->distributor('FFF'.$this->suffix);
        $b = $this->distributor('GGG'.$this->suffix);

        $component = Livewire::actingAs($this->admin())->test(Distributors::class);

        // Per baris.
        $component->call('toggleDistributorActive', $a->id);
        $this->assertFalse($a->fresh()->is_active);
        $component->call('toggleDistributorActive', $a->id);
        $this->assertTrue($a->fresh()->is_active);

        // Massal: keduanya sekaligus.
        $component->set('selected', [$a->id, $b->id])->call('bulkSetActive', false);
        $this->assertFalse($a->fresh()->is_active);
        $this->assertFalse($b->fresh()->is_active);

        // Pilihan dibersihkan setelah tindakan, supaya tidak terpakai ulang
        // tanpa sengaja pada tindakan berikutnya.
        $this->assertSame([], $component->get('selected'));

        $component->set('selected', [$a->id, $b->id])->call('bulkSetActive', true);
        $this->assertTrue($a->fresh()->is_active);
        $this->assertTrue($b->fresh()->is_active);
    }

    public function test_penyaring_status_dan_grup_bisa_dipakai_bersamaan(): void
    {
        $group = DistributorGroup::create(['name' => 'Saring '.$this->suffix]);

        $aktif = $this->distributor('HHH'.$this->suffix, $group);
        $nonaktif = $this->distributor('III'.$this->suffix, $group);
        $nonaktif->update(['is_active' => false]);

        $component = Livewire::actingAs($this->admin())
            ->test(Distributors::class)
            ->set('groupFilter', (string) $group->id)
            ->set('statusFilter', 'inactive');

        // Keduanya berlaku bersamaan, bukan saling menggantikan.
        $component->assertSee($nonaktif->distributor_code)
            ->assertDontSee($aktif->distributor_code);

        $component->set('statusFilter', 'active')
            ->assertSee($aktif->distributor_code)
            ->assertDontSee($nonaktif->distributor_code);

        // Tombol reset membersihkan seluruh penyaring, bukan hanya pencarian.
        $component->call('resetFilters');
        $this->assertSame('', $component->get('statusFilter'));
        $this->assertSame('', $component->get('groupFilter'));
    }
}
