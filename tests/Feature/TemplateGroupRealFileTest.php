<?php

namespace Tests\Feature;

use App\Models\DistributorTemplateGroup;
use App\Services\StockImportService;
use App\Support\StockFileReader;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Resep tiap grup diuji terhadap berkas distributor yang SESUNGGUHNYA.
 *
 * Contoh buatan sendiri selalu lolos — ia dibuat mengikuti resepnya. Yang
 * benar-benar menangkap kekeliruan adalah berkas aslinya, lengkap dengan kop
 * surat, pivot table, sheet sampah berisi sejuta baris, baris total, dan
 * tanggal yang cuma tertulis di nama berkas.
 *
 * Berkasnya tidak ikut masuk repositori (lihat .gitignore), jadi test ini
 * melewatkan dirinya sendiri bila berkasnya tidak ada — dan tetap berguna di
 * mesin yang punya berkasnya.
 */
class TemplateGroupRealFileTest extends TestCase
{
    use DatabaseTransactions;

    private const DIR = 'template excel distributor';

    /**
     * @return array<string, array{0: string, 1: string, 2: int, 3: int}>
     *         berkas => [grup, kode cabang yang diharapkan (null bila berupa
     *         teks tetap yang boleh diubah operator), jumlah cabang, tanggal]
     */
    public static function fileProvider(): array
    {
        return [
            'UDC' => ['DataStockCutOffSATORIA_ANEKA INDUSTRI_21_09_2026 - UDC.xlsx', 'UDC', 'UDCJAKARTA1', 23, '2026-09-21'],
            'KFTD' => ['Stock Satoria 23 September 2026.XLSX', 'KFTD', 'KFTDMEDAN', 46, '2026-09-23'],
            'PPI' => ['Data Stok Satoria PPI Per 21 September 2026.xlsx', 'PPI', 'BDG-GU-02', 34, '2026-09-21'],
            'SDL' => ['STOCK SDL ALL CABANG PER 23 SEPT 2026.xlsx', 'SDL', 'SDLSEMARANG', 4, '2026-09-23'],
            // GMP dan MAM berkasnya satu cabang, dan kodenya berupa teks tetap
            // yang memang boleh diganti operator lewat UI — jadi yang diuji
            // jumlah cabang dan tanggalnya, bukan kode tertentu.
            'GMP' => ['STOCK SATORIA.xls', 'GMP', null, 1, '2026-09-22'],
            'MAM' => ['Stok Satoria Tgl. 23-09-26.xlsx', 'MAM', null, 1, '2026-09-23'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('fileProvider')]
    public function test_berkas_nyata_terbaca_oleh_resep_grupnya(
        string $file,
        string $expectedGroup,
        ?string $expectedCode,
        int $expectedBranches,
        string $expectedDate
    ): void {
        $path = base_path(self::DIR.'/'.$file);

        if (! is_file($path)) {
            $this->markTestSkipped("Berkas contoh tidak tersedia: {$file}");
        }

        if (! DistributorTemplateGroup::where('name', $expectedGroup)->exists()) {
            $this->markTestSkipped("Grup {$expectedGroup} belum di-seed.");
        }

        $reading = (new StockFileReader(app(StockImportService::class)))->read($path, $file);

        $this->assertTrue($reading['ok'], "Berkas {$file} gagal dibaca: ".($reading['error'] ?? ''));

        // Grup yang terpilih harus grup yang benar — beberapa grup bisa
        // sama-sama sanggup membaca satu bentuk berkas.
        $this->assertSame($expectedGroup, $reading['group']?->name);

        $buckets = $reading['buckets'];
        $this->assertCount($expectedBranches, $buckets, 'Jumlah cabang yang terbaca berubah.');

        if ($expectedCode !== null) {
            $this->assertArrayHasKey($expectedCode, $buckets, 'Kode cabang tidak terbentuk seperti yang diharapkan.');
        }

        // Tanggal: bagian tersulit, karena lima dari enam berkas tidak punya
        // kolom tanggal sama sekali.
        $entry = $buckets[$expectedCode ?? array_key_first($buckets)][0];
        $this->assertSame($expectedDate, $entry['reader']->tanggal($entry['row']));

        $this->assertGreaterThan(0, $entry['reader']->quantity($entry['row']));

        // Nomor batch adalah syarat agar barisnya bisa tersimpan. Untuk grup
        // yang berkasnya memang tidak punya kolom batch, syarat itu dipenuhi
        // oleh 'batch pengganti' — dan kalau setelan itu kosong, seluruh
        // berkasnya akan ditolak saat impor. Karena itu diuji di sini, bukan
        // ditemukan berminggu-minggu kemudian.
        $this->assertNotNull(
            $entry['reader']->batchNo($entry['row']),
            "Grup {$expectedGroup} tidak menghasilkan nomor batch: isi setelan 'Batch pengganti', ".
            'kalau tidak seluruh berkasnya akan ditolak saat impor.'
        );
    }

    public function test_sheet_raksasa_tidak_ikut_dimuat(): void
    {
        $path = base_path(self::DIR.'/Data Stok Satoria PPI Per 21 September 2026.xlsx');

        if (! is_file($path)) {
            $this->markTestSkipped('Berkas contoh PPI tidak tersedia.');
        }

        // Berkas ini menyimpan sheet berisi 1.047.533 baris yang tak terpakai.
        // Sebelum dijaga, memuatnya menghabiskan memori sampai proses mati.
        $before = memory_get_peak_usage(true);
        (new StockFileReader(app(StockImportService::class)))->read($path, 'ppi.xlsx');
        $usedMb = (memory_get_peak_usage(true) - $before) / 1048576;

        $this->assertLessThan(400, $usedMb, 'Pembacaan memakai memori jauh di atas yang wajar.');
    }

    public function test_sheet_yang_diminta_eksplisit_tetap_terbaca_walau_metadatanya_raksasa(): void
    {
        $path = base_path(self::DIR.'/Data Stok Satoria PPI Per 21 September 2026.xlsx');

        if (! is_file($path)) {
            $this->markTestSkipped('Berkas contoh PPI tidak tersedia.');
        }

        // Sheet 'Sheet1' pada berkas ini dilaporkan Excel berisi 1.047.533
        // baris (sisa format sel lama), padahal isi sungguhannya cuma
        // puluhan baris. Penjagaan ukuran hanya boleh mencegah DETEKSI
        // OTOMATIS memilihnya — begitu operator menyebut namanya sendiri di
        // Grup Template, sheet itu wajib tetap terbaca.
        $group = new DistributorTemplateGroup([
            'name' => 'PPI Sheet1 uji'.substr(uniqid(), -6),
            'sheet_name' => 'Sheet1',
            'column_map' => [
                'Tanggal' => 'date',
                'ID DISTRIBUTOR' => 'class_id',
                'Distributor Item Name' => 'product_name',
                'Quantity' => 'class_name',
            ],
        ]);

        $reading = (new StockFileReader(app(StockImportService::class)))->read($path, 'ppi.xlsx', $group);

        $this->assertNotSame(
            'Sheet "Sheet1" tidak ada pada berkas ini.',
            $reading['error'] ?? null,
        );
        $this->assertContains('Sheet1', $reading['sheets']);
    }
}
