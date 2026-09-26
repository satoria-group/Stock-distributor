<?php

namespace Tests\Feature;

use App\Models\Distributor;
use App\Models\DistributorItem;
use App\Models\NetsuiteItem;
use App\Models\StockEmailLog;
use App\Models\StockEntry;
use App\Services\StockImportService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/**
 * Otomasi email untuk berkas berisi beberapa cabang harus berperilaku seperti
 * halaman Upload: kondisi wajar per cabang (data sudah ada, semua item belum
 * ter-mapping) hanya mengenai cabang itu, bukan membatalkan seisi berkas.
 */
class EmailAutomationBranchSkipTest extends TestCase
{
    use DatabaseTransactions;

    private const SENDER = 'stok@cabang-uji.co.id';

    private string $suffix;

    protected function setUp(): void
    {
        parent::setUp();
        $this->suffix = strtoupper(substr(uniqid(), -6));
    }

    /** @return array<string, Distributor> kunci = nama cabang */
    private function seedBranches(array $branchNames, array $unmappedOnly = []): array
    {
        $netsuite = NetsuiteItem::create([
            'netsuite_id' => 'NS-'.$this->suffix,
            'netsuite_name' => 'Produk Uji '.$this->suffix,
        ]);

        $distributors = [];
        foreach ($branchNames as $branch) {
            $distributor = Distributor::create([
                'distributor_code' => 'BR'.$this->suffix.strtoupper($branch),
                'name' => 'Cabang '.$branch,
                'sender_email' => self::SENDER,
                'is_active' => true,
            ]);

            if (! in_array($branch, $unmappedOnly, true)) {
                DistributorItem::create([
                    'distributor_id' => $distributor->id,
                    'item_name' => 'SUSU 400G',
                    'satuan' => 'PCS',
                    'netsuite_item_id' => $netsuite->id,
                ]);
            }

            $distributors[$branch] = $distributor;
        }

        return $distributors;
    }

    /** @param array<string, array<string, int>> $branches kode => [item => qty] */
    private function excel(array $branches): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template');
        $sheet->fromArray(['Tanggal', 'ID DISTRIBUTOR', 'Distributor Item Name', 'Quantity', 'Satuan', 'ED', 'Batch No'], null, 'A1');

        $rowNo = 2;
        foreach ($branches as $code => $items) {
            foreach ($items as $itemName => $qty) {
                $sheet->fromArray(['22/09/2026', $code, $itemName, $qty, 'PCS', '31/12/2027', 'B-'.$rowNo], null, 'A'.$rowNo);
                $rowNo++;
            }
        }

        $path = tempnam(sys_get_temp_dir(), 'branch_skip_').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        $content = file_get_contents($path);
        @unlink($path);

        return $content;
    }

    private function process(string $content, string $uid): array
    {
        return app(StockImportService::class)->processEmailAttachment(
            binaryContent: $content,
            filename: 'stok.xlsx',
            fromEmail: self::SENDER,
            subject: 'Satoria Daily Stock',
            emailUid: $uid,
        );
    }

    /** A2: cabang yang datanya sudah ada dilewati, cabang lain tetap masuk. */
    public function test_cabang_dengan_data_sudah_ada_dilewati_cabang_lain_tetap_masuk(): void
    {
        $d = $this->seedBranches(['JKT', 'BDG', 'SBY']);

        // JKT sudah diisi manual lebih dulu untuk tanggal yang sama.
        $existing = StockEntry::create([
            'tanggal' => '2026-09-22',
            'distributor_id' => $d['JKT']->id,
            'distributor_item_id' => DistributorItem::where('distributor_id', $d['JKT']->id)->value('id'),
            'quantity' => 999,
            'batch_no' => 'MANUAL-1',
        ]);

        $result = $this->process($this->excel([
            $d['JKT']->distributor_code => ['SUSU 400G' => 10],
            $d['BDG']->distributor_code => ['SUSU 400G' => 20],
            $d['SBY']->distributor_code => ['SUSU 400G' => 30],
        ]), 'A2-1');

        $this->assertTrue($result['success']);
        $this->assertSame('partial_unmapped', $result['status']);
        $this->assertSame(2, $result['imported_rows']);
        $this->assertSame([$d['JKT']->distributor_code], $result['details']['skipped_existing_codes']);
        $this->assertStringContainsString('dilewati', $result['error']);

        // Data manual JKT tidak disentuh.
        $this->assertSame(1, StockEntry::where('distributor_id', $d['JKT']->id)->count());
        $this->assertEquals(999, $existing->fresh()->quantity);

        // BDG & SBY masuk.
        $this->assertEquals(20, StockEntry::where('distributor_id', $d['BDG']->id)->value('quantity'));
        $this->assertEquals(30, StockEntry::where('distributor_id', $d['SBY']->id)->value('quantity'));

        $log = StockEmailLog::where('email_uid', 'A2-1')->first();
        $this->assertSame('partial_unmapped', $log->status);
        $this->assertSame($d['BDG']->id, $log->distributor_id);
        $this->assertCount(3, $log->details['branches']);
    }

    /** A2: bila SEMUA cabang sudah punya data, berkas tetap ditolak sebagai data_already_exists. */
    public function test_semua_cabang_sudah_ada_tetap_data_already_exists(): void
    {
        $d = $this->seedBranches(['JKT', 'BDG']);

        foreach ($d as $dist) {
            StockEntry::create([
                'tanggal' => '2026-09-22',
                'distributor_id' => $dist->id,
                'distributor_item_id' => DistributorItem::where('distributor_id', $dist->id)->value('id'),
                'quantity' => 5,
                'batch_no' => 'MANUAL-1',
            ]);
        }

        $result = $this->process($this->excel([
            $d['JKT']->distributor_code => ['SUSU 400G' => 10],
            $d['BDG']->distributor_code => ['SUSU 400G' => 20],
        ]), 'A2-2');

        $this->assertFalse($result['success']);
        $this->assertSame('data_already_exists', $result['status']);
        $this->assertSame(0, $result['imported_rows']);
    }

    /** A1: cabang yang semua itemnya belum ter-mapping tidak membatalkan cabang lain. */
    public function test_cabang_semua_belum_ter_mapping_tidak_membatalkan_cabang_lain(): void
    {
        $d = $this->seedBranches(['JKT', 'SBY'], unmappedOnly: ['SBY']);

        $result = $this->process($this->excel([
            $d['JKT']->distributor_code => ['SUSU 400G' => 10],
            $d['SBY']->distributor_code => ['PRODUK BARU X' => 7, 'PRODUK BARU Y' => 8],
        ]), 'A1-1');

        $this->assertTrue($result['success']);
        $this->assertSame('partial_unmapped', $result['status']);
        $this->assertEquals(10, StockEntry::where('distributor_id', $d['JKT']->id)->value('quantity'));
        $this->assertSame(0, StockEntry::where('distributor_id', $d['SBY']->id)->count());

        // Item baru benar-benar tercatat di antrean mapping, tidak ikut rollback.
        $queued = DistributorItem::lookupFor($d['SBY']);
        $this->assertTrue($queued->has('produk baru x'));
        $this->assertTrue($queued->has('produk baru y'));
        $this->assertEqualsCanonicalizing(['PRODUK BARU X', 'PRODUK BARU Y'], $result['details']['unique_skipped_names']);
    }

    /** A1: berkas satu cabang yang semuanya belum ter-mapping tetap mengisi antrean mapping. */
    public function test_satu_cabang_semua_belum_ter_mapping_tetap_masuk_antrean(): void
    {
        $d = $this->seedBranches(['SBY'], unmappedOnly: ['SBY']);

        $result = $this->process($this->excel([
            $d['SBY']->distributor_code => ['PRODUK BARU Z' => 3],
        ]), 'A1-2');

        $this->assertFalse($result['success']);
        $this->assertSame('all_unmapped', $result['status']);
        $this->assertTrue(DistributorItem::lookupFor($d['SBY'])->has('produk baru z'));
    }

    /** Kesalahan berkas sungguhan tetap membatalkan SELURUH berkas. */
    public function test_kesalahan_batch_tetap_membatalkan_seluruh_berkas(): void
    {
        $d = $this->seedBranches(['JKT', 'BDG']);

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray(['Tanggal', 'ID DISTRIBUTOR', 'Distributor Item Name', 'Quantity', 'Satuan', 'ED', 'Batch No'], null, 'A1');
        $sheet->fromArray(['22/09/2026', $d['JKT']->distributor_code, 'SUSU 400G', 10, 'PCS', '31/12/2027', 'B-1'], null, 'A2');
        // Batch kosong pada cabang kedua.
        $sheet->fromArray(['22/09/2026', $d['BDG']->distributor_code, 'SUSU 400G', 20, 'PCS', '31/12/2027', ''], null, 'A3');
        $path = tempnam(sys_get_temp_dir(), 'branch_err_').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        $content = file_get_contents($path);
        @unlink($path);

        $result = $this->process($content, 'ERR-1');

        $this->assertFalse($result['success']);
        $this->assertSame('invalid_batch_data', $result['status']);
        $this->assertSame(0, StockEntry::whereIn('distributor_id', [$d['JKT']->id, $d['BDG']->id])->count());
    }
}
