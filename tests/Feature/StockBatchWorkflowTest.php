<?php

namespace Tests\Feature;

use App\Livewire\Dashboard;
use App\Livewire\Stock\Upload;
use App\Models\Distributor;
use App\Models\DistributorItem;
use App\Models\NetsuiteItem;
use App\Models\StockEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Alur batch ujung-ke-ujung lewat komponen sungguhan (bukan hanya unit).
 *
 * Menjaga inti permintaan pengguna: nomor batch dicatat sendiri-sendiri,
 * tidak digabungkan.
 */
class StockBatchWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    private Distributor $dist;

    private DistributorItem $item;

    private string $tanggal = '2026-09-30';

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::where('email', 'admin@satoriagroup.co.id')->first() ?? User::first();
        if (! $this->user) {
            $this->user = User::factory()->create();
            $this->user->syncRoles([User::ROLE_ADMIN]);
        }

        $this->dist = Distributor::create([
            'distributor_code' => 'E2E_BATCH_'.uniqid(),
            'name' => 'Distributor E2E Batch',
            'is_active' => true,
        ]);

        $ns = NetsuiteItem::create([
            'netsuite_id' => 'NS_'.uniqid(),
            'netsuite_name' => 'Produk E2E Batch',
        ]);

        $this->item = DistributorItem::create([
            'distributor_id' => $this->dist->id,
            'item_name' => 'Produk E2E Batch',
            'satuan' => 'BTL',
            'netsuite_item_id' => $ns->id,
        ]);
    }

    private function row(string $batch, float $qty, string $ed): array
    {
        return [
            'distributor_item_id' => $this->item->id,
            'satuan' => 'BTL',
            'quantity' => $qty,
            'expired_date' => $ed,
            'batch_no' => $batch,
        ];
    }

    private function save(array $rows, ?callable $before = null): void
    {
        $c = Livewire::actingAs($this->user)
            ->test(Upload::class)
            ->set('tanggal', $this->tanggal)
            ->set('distributorId', $this->dist->id);

        if ($before) {
            $before($c);
        }

        $c->call('saveRows', $rows);
    }

    public function test_two_batches_of_one_item_are_saved_as_two_rows(): void
    {
        $this->save([
            $this->row('IGMP-02', 500, '20/06/2028'),
            $this->row('IGMP-02-A', 300, '15/11/2029'),
        ]);

        $entries = StockEntry::where('distributor_item_id', $this->item->id)->get()->keyBy('batch_no');

        $this->assertCount(2, $entries);
        $this->assertEquals(500.0, (float) $entries['IGMP-02']->quantity);
        $this->assertEquals(300.0, (float) $entries['IGMP-02-A']->quantity);
    }

    /**
     * Inti kerugian dari peleburan lama: ED tiap batch harus berdiri sendiri,
     * sehingga stok yang masih lama tidak ikut ditandai mendekati kedaluwarsa.
     */
    public function test_each_batch_keeps_its_own_expiry_status(): void
    {
        $this->save([
            $this->row('BATCH-DEKAT', 500, '20/06/2026'),   // sudah lewat
            $this->row('BATCH-JAUH', 300, '15/11/2029'),    // masih lama
        ]);

        $entries = StockEntry::where('distributor_item_id', $this->item->id)->get()->keyBy('batch_no');

        $this->assertSame('expired', $entries['BATCH-DEKAT']->expiryStatus());
        $this->assertSame('safe', $entries['BATCH-JAUH']->expiryStatus());
    }

    public function test_deleting_one_batch_keeps_the_other(): void
    {
        $this->save([
            $this->row('IGMP-02', 500, '20/06/2028'),
            $this->row('IGMP-02-A', 300, '15/11/2029'),
        ]);

        $this->save(
            [$this->row('IGMP-02-A', 300, '15/11/2029')],
            fn ($c) => $c->call('markRowRemoved', $this->item->id, 'IGMP-02')
        );

        $sisa = StockEntry::where('distributor_item_id', $this->item->id)->pluck('batch_no')->all();

        $this->assertSame(['IGMP-02-A'], $sisa, 'Menghapus satu batch tidak boleh ikut membuang batch lain.');
    }

    public function test_saving_same_batch_again_updates_instead_of_duplicating(): void
    {
        $this->save([$this->row('IGMP-02', 500, '20/06/2028')]);
        $this->save([$this->row('IGMP-02', 750, '20/06/2028')]);

        $entries = StockEntry::where('distributor_item_id', $this->item->id)->get();

        $this->assertCount(1, $entries);
        $this->assertEquals(750.0, (float) $entries->first()->quantity);
    }

    public function test_dashboard_renders_with_multi_batch_data(): void
    {
        $this->save([
            $this->row('IGMP-02', 500, '20/06/2028'),
            $this->row('IGMP-02-A', 300, '15/11/2029'),
        ]);

        foreach (['stock', 'expiry', 'compliance'] as $tab) {
            Livewire::actingAs($this->user)
                ->test(Dashboard::class)
                ->set('activeTab', $tab)
                ->assertStatus(200);
        }
    }
}
