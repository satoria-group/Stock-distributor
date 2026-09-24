<?php

namespace Tests\Feature;

use App\Models\Distributor;
use App\Models\DistributorGroup;
use App\Models\DistributorItem;
use App\Models\NetsuiteItem;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Baris pemetaan yang pernah dihapus lalu muncul lagi dari berkas.
 *
 * Barisnya memang harus DIPULIHKAN, bukan diganti baris baru — snapshot stok
 * menunjuk ke baris itu, dan membuat baris kedua memecah satu item menjadi dua
 * di seluruh laporan.
 *
 * Tapi pemulihannya tidak boleh membawa pemetaan lamanya: menghapus baris
 * pemetaan hampir selalu berarti "nama ini salah" atau "pemetaannya keliru".
 */
class ItemRestoreTest extends TestCase
{
    use DatabaseTransactions;

    private string $suffix;

    private Distributor $distributor;

    private NetsuiteItem $netsuite;

    protected function setUp(): void
    {
        parent::setUp();
        $this->suffix = strtoupper(substr(uniqid(), -6));

        $group = DistributorGroup::create(['name' => 'Pulih '.$this->suffix]);

        $this->distributor = Distributor::create([
            'distributor_code' => 'PLH'.$this->suffix,
            'name' => 'Distributor Pulih '.$this->suffix,
            'is_active' => true,
            'distributor_group_id' => $group->id,
        ]);

        $this->netsuite = NetsuiteItem::create([
            'netsuite_id' => 'NS-'.$this->suffix,
            'netsuite_name' => 'Produk '.$this->suffix,
        ]);
    }

    public function test_item_yang_dihapus_kembali_tanpa_membawa_pemetaan_lamanya(): void
    {
        $item = DistributorItem::queueFor($this->distributor, 'SUSU 400G', 'PCS');
        $item->update(['netsuite_item_id' => $this->netsuite->id]);

        $item->delete();

        $kembali = DistributorItem::queueFor($this->distributor, 'SUSU 400G', 'PCS');

        // Baris yang sama, bukan baris baru — riwayat snapshot tetap menyambung.
        $this->assertSame($item->id, $kembali->id);
        $this->assertFalse($kembali->trashed());

        // Tapi pemetaannya kosong lagi: keputusan menghapus tidak dibatalkan
        // diam-diam.
        $this->assertNull($kembali->netsuite_item_id);
        $this->assertFalse($kembali->isMapped());
    }

    public function test_item_yang_masih_hidup_tidak_kehilangan_pemetaannya(): void
    {
        $item = DistributorItem::queueFor($this->distributor, 'SUSU 900G', 'PCS');
        $item->update(['netsuite_item_id' => $this->netsuite->id]);

        // Berkas berikutnya memuat nama yang sama — ini kejadian sehari-hari,
        // dan pemetaannya tidak boleh ikut hilang.
        $lagi = DistributorItem::queueFor($this->distributor, 'susu 900g', 'BOX');

        $this->assertSame($item->id, $lagi->id);
        $this->assertSame($this->netsuite->id, $lagi->netsuite_item_id);
    }

    public function test_antrean_mendarat_di_grup_bukan_di_tiap_cabang(): void
    {
        $item = DistributorItem::queueFor($this->distributor, 'SUSU 1L');

        $this->assertSame($this->distributor->distributor_group_id, $item->distributor_group_id);
        $this->assertNull($item->distributor_id, 'Item baru seharusnya milik grup, bukan satu cabang.');
    }

    public function test_distributor_tanpa_grup_mendapat_baris_miliknya_sendiri(): void
    {
        $sendiri = Distributor::create([
            'distributor_code' => 'SOLO'.$this->suffix,
            'name' => 'Distributor Tunggal '.$this->suffix,
            'is_active' => true,
        ]);

        $item = DistributorItem::queueFor($sendiri, 'SUSU 2L');

        $this->assertSame($sendiri->id, $item->distributor_id);
        $this->assertNull($item->distributor_group_id);
    }
}
