<?php

namespace Tests\Feature;

use App\Models\Distributor;
use App\Models\DistributorGroup;
use App\Models\DistributorItem;
use App\Models\NetsuiteItem;
use App\Models\StockEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Pemetaan item dan whitelist email kini milik GRUP USAHA.
 *
 * Satu berkas memuat seluruh cabang dengan nama item yang sama persis, jadi
 * memetakan satu item sekali harus berlaku untuk semua cabangnya — dan satu
 * alamat pengirim cukup didaftarkan sekali.
 */
class GroupLevelMappingTest extends TestCase
{
    use DatabaseTransactions;

    private string $suffix;

    private DistributorGroup $group;

    private NetsuiteItem $netsuite;

    protected function setUp(): void
    {
        parent::setUp();
        $this->suffix = strtoupper(substr(uniqid(), -6));

        $this->group = DistributorGroup::create(['name' => 'Grup '.$this->suffix]);
        $this->netsuite = NetsuiteItem::create([
            'netsuite_id' => 'NS-'.$this->suffix,
            'netsuite_name' => 'Produk '.$this->suffix,
        ]);
    }

    private function branch(string $city): Distributor
    {
        return Distributor::create([
            'distributor_code' => 'GRP'.$this->suffix.$city,
            'name' => 'Cabang '.$city.' '.$this->suffix,
            'is_active' => true,
            'distributor_group_id' => $this->group->id,
        ]);
    }

    public function test_satu_pemetaan_grup_berlaku_untuk_semua_cabang(): void
    {
        $bandung = $this->branch('BANDUNG');
        $medan = $this->branch('MEDAN');

        // Dipetakan SEKALI di level grup.
        DistributorItem::create([
            'distributor_group_id' => $this->group->id,
            'item_name' => 'SUSU 400G',
            'satuan' => 'PCS',
            'netsuite_item_id' => $this->netsuite->id,
        ]);

        foreach ([$bandung, $medan] as $branch) {
            $lookup = DistributorItem::lookupFor($branch);

            $this->assertTrue($lookup->has('susu 400g'), "Cabang {$branch->name} tidak melihat pemetaan grupnya.");
            $this->assertTrue($lookup->get('susu 400g')->isMapped());
        }
    }

    public function test_pemetaan_cabang_menimpa_pemetaan_grup(): void
    {
        $bandung = $this->branch('BANDUNG');

        $lainnya = NetsuiteItem::create([
            'netsuite_id' => 'NS2-'.$this->suffix,
            'netsuite_name' => 'Produk Lain '.$this->suffix,
        ]);

        DistributorItem::create([
            'distributor_group_id' => $this->group->id,
            'item_name' => 'SUSU 400G',
            'netsuite_item_id' => $this->netsuite->id,
        ]);

        // Cabang ini memetakan nama yang sama ke produk berbeda.
        DistributorItem::create([
            'distributor_id' => $bandung->id,
            'item_name' => 'SUSU 400G',
            'netsuite_item_id' => $lainnya->id,
        ]);

        $this->assertSame(
            $lainnya->id,
            DistributorItem::lookupFor($bandung)->get('susu 400g')->netsuite_item_id,
            'Pengecualian cabang harus menang atas pemetaan grup.'
        );
    }

    public function test_dua_cabang_bisa_menyimpan_item_dan_batch_sama_pada_tanggal_sama(): void
    {
        $bandung = $this->branch('BANDUNG');
        $medan = $this->branch('MEDAN');

        $item = DistributorItem::create([
            'distributor_group_id' => $this->group->id,
            'item_name' => 'SUSU 400G',
            'netsuite_item_id' => $this->netsuite->id,
        ]);

        $user = User::factory()->create();

        // Satu baris pemetaan dipakai dua cabang: tanpa distributor_id sebagai
        // bagian identitas, baris kedua akan menimpa yang pertama.
        foreach ([[$bandung, 120], [$medan, 85]] as [$branch, $qty]) {
            StockEntry::updateOrCreate(
                [
                    'tanggal' => '2026-09-23',
                    'distributor_id' => $branch->id,
                    'distributor_item_id' => $item->id,
                    'batch_no' => 'B-1',
                ],
                [
                    'quantity' => $qty,
                    'satuan' => 'PCS',
                    'uploaded_by' => $user->id,
                ]
            );
        }

        $this->assertSame(120.0, (float) StockEntry::where('distributor_id', $bandung->id)
            ->where('tanggal', '2026-09-23')->sum('quantity'));
        $this->assertSame(85.0, (float) StockEntry::where('distributor_id', $medan->id)
            ->where('tanggal', '2026-09-23')->sum('quantity'));
    }

    public function test_whitelist_grup_berlaku_untuk_cabang_yang_tak_mengisinya(): void
    {
        $this->group->update(['sender_email' => 'laporan@grup.co.id']);

        $ikut = $this->branch('BANDUNG');
        $this->assertSame('laporan@grup.co.id', $ikut->fresh()->effectiveSenderEmail());
        $this->assertTrue($ikut->fresh()->senderEmailIsInherited());

        // Cabang yang mengirim dari alamat lain menimpanya.
        $menyimpang = $this->branch('MEDAN');
        $menyimpang->update(['sender_email' => 'khusus@cabang.co.id']);

        $this->assertSame('khusus@cabang.co.id', $menyimpang->fresh()->effectiveSenderEmail());
        $this->assertFalse($menyimpang->fresh()->senderEmailIsInherited());
    }

    public function test_distributor_tanpa_grup_tetap_memakai_pemetaannya_sendiri(): void
    {
        $sendiri = Distributor::create([
            'distributor_code' => 'SOLO'.$this->suffix,
            'name' => 'Distributor Tunggal '.$this->suffix,
            'is_active' => true,
        ]);

        DistributorItem::create([
            'distributor_id' => $sendiri->id,
            'item_name' => 'SUSU 400G',
            'netsuite_item_id' => $this->netsuite->id,
        ]);

        // Pemetaan grup lain tidak boleh bocor ke distributor tanpa grup.
        DistributorItem::create([
            'distributor_group_id' => $this->group->id,
            'item_name' => 'SUSU 900G',
            'netsuite_item_id' => $this->netsuite->id,
        ]);

        $lookup = DistributorItem::lookupFor($sendiri);

        $this->assertTrue($lookup->has('susu 400g'));
        $this->assertFalse($lookup->has('susu 900g'));
    }
}
