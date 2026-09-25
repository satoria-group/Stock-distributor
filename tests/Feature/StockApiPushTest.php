<?php

namespace Tests\Feature;

use App\Models\ApiClient;
use App\Models\Distributor;
use App\Models\DistributorGroup;
use App\Models\DistributorItem;
use App\Models\NetsuiteItem;
use App\Models\StockApiLog;
use App\Models\StockEntry;
use App\Models\StockSnapshotActivity;
use App\Models\UnitConversion;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * API push stok (POST /api/v1/stock): distributor dengan sistem sendiri
 * mengirim snapshot stok, diproses dengan jalur impor yang sama seperti Excel.
 */
class StockApiPushTest extends TestCase
{
    use DatabaseTransactions;

    private string $suffix;

    private DistributorGroup $group;

    private Distributor $branch;

    private string $token;

    private ApiClient $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->suffix = strtoupper(substr(uniqid(), -6));

        $this->group = DistributorGroup::create(['name' => 'Grup API '.$this->suffix]);
        $this->branch = $this->branch('JKT');

        $netsuite = NetsuiteItem::create([
            'netsuite_id' => 'NS-API-'.$this->suffix,
            'netsuite_name' => 'Produk API '.$this->suffix,
        ]);
        DistributorItem::create([
            'distributor_group_id' => $this->group->id,
            'item_name' => 'SUSU API '.$this->suffix,
            'netsuite_item_id' => $netsuite->id,
        ]);

        [$this->client, $this->token] = ApiClient::register([
            'name' => 'Klien '.$this->suffix,
            'distributor_group_id' => $this->group->id,
        ]);
    }

    private function branch(string $city, ?DistributorGroup $group = null, bool $active = true): Distributor
    {
        return Distributor::create([
            'distributor_code' => 'API'.$this->suffix.$city,
            'name' => 'Cabang '.$city.' '.$this->suffix,
            'is_active' => $active,
            'distributor_group_id' => ($group ?? $this->group)->id,
        ]);
    }

    private function item(array $overrides = []): array
    {
        return $overrides + [
            'item_name' => 'SUSU API '.$this->suffix,
            'qty' => 100,
            'unit' => 'PCS',
            'batch_no' => 'B-001',
            'expired_date' => '2027-12-31',
        ];
    }

    private function payload(?array $items = null, ?string $code = null, array $overrides = []): array
    {
        return $overrides + [
            'request_id' => 'REQ-'.uniqid(),
            'stock_date' => '2026-09-25',
            'branches' => [
                ['distributor_code' => $code ?? $this->branch->distributor_code, 'items' => $items ?? [$this->item()]],
            ],
        ];
    }

    private function push(array $payload, ?string $token = null)
    {
        return $this->withToken($token ?? $this->token)->postJson('/api/v1/stock', $payload);
    }

    private function entries()
    {
        return StockEntry::where('distributor_id', $this->branch->id)->where('tanggal', '2026-09-25');
    }

    public function test_tanpa_token_atau_token_salah_ditolak(): void
    {
        $this->postJson('/api/v1/stock', $this->payload())->assertStatus(401);
        $this->push($this->payload(), 'stk_salah')->assertStatus(401);

        $this->client->update(['is_active' => false]);
        $this->push($this->payload())->assertStatus(401);

        $this->assertSame(0, $this->entries()->count());
    }

    public function test_ip_di_luar_whitelist_ditolak(): void
    {
        $this->client->update(['allowed_ips' => '203.0.113.10']);

        $this->push($this->payload())->assertStatus(403)->assertJson(['status' => 'forbidden_ip']);
    }

    public function test_kiriman_sah_tersimpan_sebagai_snapshot(): void
    {
        $res = $this->push($this->payload());

        $res->assertOk()->assertJson(['status' => 'success', 'imported_rows' => 1, 'stock_date' => '2026-09-25']);

        $entry = $this->entries()->sole();
        $this->assertEquals(100, $entry->quantity);
        $this->assertSame('B-001', $entry->batch_no);
        $this->assertSame('2027-12-31', $entry->expired_date->format('Y-m-d'));

        $activity = StockSnapshotActivity::forSnapshot('2026-09-25', $this->branch->id)->latest('id')->first();
        $this->assertSame('api', $activity->metadata['source']);
        $this->assertNotNull($this->client->fresh()->last_used_at);
    }

    public function test_kiriman_baru_mengganti_snapshot_tanggal_yang_sama(): void
    {
        $this->push($this->payload([
            $this->item(['batch_no' => 'B-001']),
            $this->item(['batch_no' => 'B-002', 'qty' => 5]),
        ]))->assertOk();
        $this->assertSame(2, $this->entries()->count());

        // Batch B-002 tidak dikirim lagi: stoknya dianggap habis.
        $this->push($this->payload([$this->item(['batch_no' => 'B-001', 'qty' => 40])]))->assertOk();

        $entry = $this->entries()->sole();
        $this->assertSame('B-001', $entry->batch_no);
        $this->assertEquals(40, $entry->quantity);
    }

    public function test_request_id_ganda_ditolak_dan_tidak_diproses_ulang(): void
    {
        $payload = $this->payload();
        $this->push($payload)->assertOk();

        $this->push($payload)->assertStatus(409)->assertJson(['status' => 'duplicate_request']);

        $this->assertSame(1, StockApiLog::where('api_client_id', $this->client->id)->count());
    }

    public function test_cabang_milik_grup_lain_ditolak(): void
    {
        $other = DistributorGroup::create(['name' => 'Grup Lain '.$this->suffix]);
        $foreign = $this->branch('LAIN', $other);

        $this->push($this->payload(null, $foreign->distributor_code))
            ->assertStatus(403)
            ->assertJson(['status' => 'forbidden_distributor']);

        $this->assertSame(0, StockEntry::where('distributor_id', $foreign->id)->count());
    }

    public function test_kode_cabang_tidak_dikenal_dan_non_aktif_ditolak(): void
    {
        $this->push($this->payload(null, 'TIDAK_ADA_'.$this->suffix))->assertStatus(422)->assertJson(['status' => 'unknown_distributor']);

        $inactive = $this->branch('MATI', null, false);
        $this->push($this->payload(null, $inactive->distributor_code))->assertStatus(422)->assertJson(['status' => 'inactive_distributor']);
    }

    public function test_satu_cabang_gagal_membatalkan_seluruh_kiriman(): void
    {
        $inactive = $this->branch('MATI', null, false);

        $payload = $this->payload();
        $payload['branches'][] = ['distributor_code' => $inactive->distributor_code, 'items' => [$this->item()]];

        $this->push($payload)->assertStatus(422);
        $this->assertSame(0, $this->entries()->count());
    }

    public function test_item_belum_ter_mapping_masuk_antrean_dan_dilaporkan(): void
    {
        $unmapped = 'ITEM BARU '.$this->suffix;

        $res = $this->push($this->payload([$this->item(), $this->item(['item_name' => $unmapped])]));

        $res->assertOk()->assertJson(['status' => 'partial_unmapped', 'imported_rows' => 1, 'skipped_rows' => 1, 'unmapped_items' => [$unmapped]]);
        $this->assertTrue(DistributorItem::lookupFor($this->branch)->has(mb_strtolower($unmapped)));
    }

    public function test_semua_item_belum_ter_mapping_tidak_menghapus_snapshot_lama(): void
    {
        $this->push($this->payload())->assertOk();

        $this->push($this->payload([$this->item(['item_name' => 'ASING '.$this->suffix])]))
            ->assertStatus(422)
            ->assertJson(['status' => 'all_unmapped']);

        $this->assertSame(1, $this->entries()->count());
    }

    public function test_konversi_satuan_diterapkan(): void
    {
        $unit = 'KRT'.$this->suffix;
        UnitConversion::create(['from_unit' => $unit, 'to_unit' => 'PCS', 'factor' => 12]);

        $this->push($this->payload([$this->item(['qty' => 3, 'unit' => $unit])]))->assertOk();

        $entry = $this->entries()->sole();
        $this->assertEquals(36, $entry->quantity);
        $this->assertSame('PCS', $entry->satuan);
        $this->assertEquals(3, $entry->quantity_asli);
    }

    public function test_validasi_payload(): void
    {
        $this->push(['request_id' => 'X'])->assertStatus(422)->assertJsonValidationErrors(['stock_date', 'branches']);

        $this->push($this->payload([$this->item(['batch_no' => null, 'qty' => -1])]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['branches.0.items.0.batch_no', 'branches.0.items.0.qty']);

        $this->push($this->payload(null, null, ['stock_date' => '25/09/2026']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['stock_date']);
    }

    public function test_status_kiriman_bisa_dicek_ulang(): void
    {
        $payload = $this->payload();
        $this->push($payload)->assertOk();

        $this->withToken($this->token)->getJson('/api/v1/stock/'.$payload['request_id'])
            ->assertOk()
            ->assertJson(['status' => 'success', 'request_id' => $payload['request_id'], 'imported_rows' => 1]);

        $this->withToken($this->token)->getJson('/api/v1/stock/TIDAK-ADA')->assertStatus(404);
    }

    public function test_aktivitas_snapshot_dicatat_sebagai_api(): void
    {
        $this->push($this->payload())->assertOk();

        $activity = StockSnapshotActivity::forSnapshot('2026-09-25', $this->branch->id)->latest('id')->first();
        $this->assertSame('Import otomatis via API (1 SKU)', $activity->description);
    }
}
