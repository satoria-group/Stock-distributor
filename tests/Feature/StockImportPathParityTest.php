<?php

namespace Tests\Feature;

use App\Livewire\Stock\Upload;
use App\Models\Distributor;
use App\Models\DistributorItem;
use App\Models\NetsuiteItem;
use App\Models\StockEntry;
use App\Models\User;
use App\Services\StockImportService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/**
 * Berkas yang sama harus menghasilkan keputusan dan baris yang sama, baik
 * diunggah manual di halaman Upload maupun datang lewat otomasi email.
 * Keduanya memakai StockImportService::mapRows().
 */
class StockImportPathParityTest extends TestCase
{
    use DatabaseTransactions;

    private const SENDER = 'stok@paritas-uji.co.id';

    private Distributor $distributor;

    protected function setUp(): void
    {
        parent::setUp();

        $suffix = strtoupper(substr(uniqid(), -6));

        $this->distributor = Distributor::create([
            'distributor_code' => 'PAR'.$suffix,
            'name' => 'Distributor Paritas '.$suffix,
            'sender_email' => self::SENDER,
            'is_active' => true,
        ]);

        $netsuite = NetsuiteItem::create([
            'netsuite_id' => 'NS-PAR-'.$suffix,
            'netsuite_name' => 'Susu Paritas '.$suffix,
        ]);

        DistributorItem::create([
            'distributor_id' => $this->distributor->id,
            'item_name' => 'SUSU 400G',
            'satuan' => 'PCS',
            'netsuite_item_id' => $netsuite->id,
        ]);
    }

    /** @param array<int, array{0: string, 1: int, 2: string, 3: string}> $rows [item, qty, ED, batch] */
    private function excel(array $rows): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template');
        $sheet->fromArray(['Tanggal', 'ID DISTRIBUTOR', 'Distributor Item Name', 'Quantity', 'Satuan', 'ED', 'Batch No'], null, 'A1');

        foreach ($rows as $i => [$item, $qty, $ed, $batch]) {
            $sheet->fromArray(['22/09/2026', $this->distributor->distributor_code, $item, $qty, 'PCS', $ed, $batch], null, 'A'.($i + 2));
        }

        $path = tempnam(sys_get_temp_dir(), 'parity_').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        $content = file_get_contents($path);
        @unlink($path);

        return $content;
    }

    private function manual(string $content)
    {
        $user = User::factory()->create();
        $user->syncRoles([User::ROLE_ADMIN]);

        return Livewire::actingAs($user)
            ->test(Upload::class)
            ->set('file', UploadedFile::fake()->createWithContent('paritas.xlsx', $content))
            ->call('importFile');
    }

    private function automation(string $content, string $uid): array
    {
        return app(StockImportService::class)->processEmailAttachment(
            binaryContent: $content,
            filename: 'paritas.xlsx',
            fromEmail: self::SENDER,
            subject: 'Satoria Daily Stock',
            emailUid: $uid,
        );
    }

    /**
     * A3: dua ejaan nama item belum ter-mapping yang hanya beda spasi adalah
     * item yang SAMA, jadi batch sama dengan ED berbeda harus ditolak di
     * KEDUA jalur. Dulu otomasi tidak menormalisasi spasi ganda dan meloloskannya.
     */
    public function test_ed_bentrok_pada_item_belum_ter_mapping_ditolak_di_kedua_jalur(): void
    {
        $content = $this->excel([
            ['SUSU 400G', 10, '31/12/2027', 'OK-1'],
            ['PRODUK  BARU', 5, '31/12/2027', 'B-1'],
            ['produk baru', 6, '31/12/2028', 'B-1'],
        ]);

        $this->manual($content)->assertHasErrors('file');

        $result = $this->automation($content, 'PAR-A3');
        $this->assertFalse($result['success']);
        $this->assertSame('invalid_batch_data', $result['status']);
        $this->assertSame(0, StockEntry::where('distributor_id', $this->distributor->id)->count());
    }

    /**
     * B1: ejaan berkas yang berbeda dari master tetap dicocokkan ke item yang
     * sama, dan kedua jalur menghasilkan baris (item, batch, qty, ED) identik.
     */
    public function test_berkas_sama_menghasilkan_baris_sama_di_kedua_jalur(): void
    {
        $content = $this->excel([
            ['susu   400g', 10, '31/12/2027', 'B-1'],
            ['SUSU 400G', 5, '31/12/2027', 'B-1'],
            ['Susu 400G', 7, '30/06/2028', 'B-2'],
        ]);

        $manualRows = collect($this->manual($content)->assertHasNoErrors()->call('startQueue')->get('rows'))
            ->map(fn ($r) => [
                $r['distributor_item_id'],
                $r['item_name'],
                $r['batch_no'],
                (float) $r['quantity'],
                \Carbon\Carbon::createFromFormat('d/m/Y', $r['expired_date'])->toDateString(),
            ])
            ->sortBy(2)->values()->all();

        $result = $this->automation($content, 'PAR-B1');
        $this->assertSame('success', $result['status']);

        $savedRows = StockEntry::with('distributorItem')
            ->where('distributor_id', $this->distributor->id)
            ->where('tanggal', '2026-09-22')
            ->get()
            ->map(fn (StockEntry $e) => [
                $e->distributor_item_id,
                $e->distributorItem->item_name,
                $e->batch_no,
                (float) $e->quantity,
                $e->expired_date->toDateString(),
            ])
            ->sortBy(2)->values()->all();

        $this->assertSame($manualRows, $savedRows);
        // Nama master yang dipakai, bukan ejaan berkas.
        $this->assertSame(['SUSU 400G', 'SUSU 400G'], array_column($manualRows, 1));
        $this->assertSame([15.0, 7.0], array_column($manualRows, 3));
    }

    /**
     * C1: jejak di Riwayat snapshot dari otomasi memuat asal emailnya dengan
     * kunci metadata yang sama seperti upload manual.
     */
    public function test_riwayat_otomasi_mencatat_asal_email_seperti_upload_manual(): void
    {
        $this->automation($this->excel([
            ['SUSU 400G', 10, '31/12/2027', 'B-1'],
            ['PRODUK BARU', 1, '31/12/2027', 'N-1'],
        ]), 'PAR-C1');

        $activity = \App\Models\StockSnapshotActivity::where('distributor_id', $this->distributor->id)
            ->where('tanggal', '2026-09-22')
            ->latest('id')
            ->first();

        $this->assertNotNull($activity);
        $this->assertSame('automation', $activity->action);
        $this->assertStringContainsString('dari email '.self::SENDER.' (UID #PAR-C1)', $activity->description);

        $meta = $activity->metadata;
        $this->assertSame('email', $meta['source']);
        $this->assertSame('PAR-C1', $meta['email_uid']);
        $this->assertSame(self::SENDER, $meta['from_email']);
        $this->assertSame('Satoria Daily Stock', $meta['subject']);
        $this->assertSame('paritas.xlsx', $meta['filename']);
        $this->assertSame(1, $meta['mapped_count']);
        $this->assertSame(1, $meta['unmapped_count']);
        $this->assertSame(0, $meta['deleted_count']);
    }

    /** B2: satu item baru di beberapa batch cukup satu baris antrean mapping. */
    public function test_item_baru_di_banyak_batch_cukup_satu_antrean(): void
    {
        $result = $this->automation($this->excel([
            ['SUSU 400G', 10, '31/12/2027', 'OK-1'],
            ['PRODUK BARU', 1, '31/12/2027', 'N-1'],
            ['Produk  Baru', 2, '31/12/2028', 'N-2'],
            ['produk baru', 3, '31/12/2029', 'N-3'],
        ]), 'PAR-B2');

        $this->assertSame('partial_unmapped', $result['status']);
        $this->assertSame(1, DistributorItem::forDistributor($this->distributor)
            ->whereRaw('lower(item_name) = ?', ['produk baru'])
            ->count());
    }
}
