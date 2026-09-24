<?php

namespace Tests\Feature;

use App\Models\DistributorTemplateGroup;
use App\Services\StockImportService;
use App\Support\StockHeaderResolver;
use App\Support\StockRowReader;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Lima bentuk berkas yang sungguh beredar (UDC, GMP, SDL, MAM, KFTD).
 *
 * Masing-masing melanggar satu asumsi berbeda, jadi kelimanya diuji apa
 * adanya — bukan satu contoh buatan yang mewakili semuanya.
 */
class TemplateGroupRecipeTest extends TestCase
{
    use DatabaseTransactions;

    private string $suffix;

    protected function setUp(): void
    {
        parent::setUp();
        // Nama grup unik: data awal proyek ini sudah memuat UDC, GMP, SDL,
        // MAM, dan KFTD yang sesungguhnya.
        $this->suffix = ' uji '.substr(uniqid(), -6);
    }

    private function read(array $data, DistributorTemplateGroup $group): array
    {
        $resolved = (new StockHeaderResolver)->resolve($data, $group);
        $reader = new StockRowReader($resolved, $group, app(StockImportService::class));

        return [$resolved, $reader];
    }

    public function test_udc_tanggal_dari_tiga_kolom_dan_kode_berawalan_tetap(): void
    {
        // Tidak ada setelan 'tanggal dari beberapa kolom' yang perlu dinyalakan:
        // resep berisi tiga kolom sudah cukup menyatakan maksudnya.
        $group = DistributorTemplateGroup::create([
            'name' => 'UDC'.$this->suffix,
            'column_map' => [
                'Tanggal' => ['parts' => [['column' => 'TGL'], ['column' => 'BLN'], ['column' => 'TAHUN']]],
                'ID DISTRIBUTOR' => [
                    'parts' => [['text' => 'UDC'], ['column' => 'CUST_NAME']],
                    'nospace' => true,
                    'upper' => true,
                ],
                'Distributor Item Name' => 'ITEMNAME',
                'Quantity' => 'SOH_QTY',
                'Satuan' => 'UNITID',
                'ED' => 'ED',
                'Batch No' => 'BATCH',
            ],
        ]);

        $data = [
            ['TGL', 'BLN', 'TAHUN', 'CUST_NAME', 'ITEMNAME', 'SOH_QTY', 'UNITID', 'ED', 'BATCH'],
            ['22', '9', '2026', 'Surabaya', 'SUSU 400G', '120', 'PCS', '31/12/2027', 'B24A'],
        ];

        [$resolved, $reader] = $this->read($data, $group);
        $this->assertTrue($resolved['ok']);

        $row = $data[1];
        $this->assertSame('2026-09-22', $reader->tanggal($row));
        // Spasi pada 'Surabaya' dibuang dan hurufnya dibesarkan, lalu diberi
        // awalan tetap — inilah kode yang harus ada di Master Distributor.
        $this->assertSame('UDCSURABAYA', $reader->distributorCode($row));
        $this->assertSame(120.0, $reader->quantity($row));
        $this->assertSame('B24A', $reader->batchNo($row));
        $this->assertSame('2027-12-31', $reader->expiredDate($row));
    }

    public function test_gmp_tanpa_batch_memakai_batch_pengganti(): void
    {
        $group = DistributorTemplateGroup::create([
            'name' => 'GMP'.$this->suffix,
            'default_batch' => 'NO-BATCH',
            'column_map' => [
                'Tanggal' => 'PERIODE',
                'ID DISTRIBUTOR' => ['parts' => [['column' => 'DISTRIBUTOR']], 'nospace' => true, 'upper' => true],
                'Distributor Item Name' => 'Nama Brg',
                'Quantity' => 'Saldo Akhir',
                'Satuan' => 'Sat Trans',
            ],
        ]);

        $data = [
            ['PERIODE', 'DISTRIBUTOR', 'Nama Brg', 'Saldo Akhir', 'Sat Trans'],
            ['22/09/2026', 'IGM Pusat', 'SUSU 400G', '80', 'PCS'],
        ];

        [$resolved, $reader] = $this->read($data, $group);
        $this->assertTrue($resolved['ok']);

        $row = $data[1];
        $this->assertSame('IGMPUSAT', $reader->distributorCode($row));
        // Berkasnya tidak punya kolom batch sama sekali; tanpa nilai pengganti
        // aturan FEFO akan menolak seluruh berkas.
        $this->assertSame('NO-BATCH', $reader->batchNo($row));
        $this->assertNull($reader->expiredDate($row));
    }

    public function test_mam_kode_berakhiran_tetap(): void
    {
        $group = DistributorTemplateGroup::create([
            'name' => 'MAM'.$this->suffix,
            'default_batch' => 'NO-BATCH',
            'column_map' => [
                'Tanggal' => 'PERIODE',
                'ID DISTRIBUTOR' => [
                    'parts' => [['column' => 'Distributor'], ['text' => 'JAKARTA']],
                    'nospace' => true,
                    'upper' => true,
                ],
                'Distributor Item Name' => 'Nama produk',
                'Quantity' => 'Stock',
                'Satuan' => '*Unit',
            ],
        ]);

        $data = [
            ['PERIODE', 'Distributor', 'Nama produk', 'Stock', '*Unit'],
            ['22/09/2026', 'MAM', 'SUSU 400G', '55', 'BOX'],
        ];

        [$resolved, $reader] = $this->read($data, $group);
        $this->assertTrue($resolved['ok']);
        $this->assertSame('MAMJAKARTA', $reader->distributorCode($data[1]));
        $this->assertSame('BOX', $reader->satuan($data[1]));
    }

    public function test_kftd_melewati_baris_berstok_nol(): void
    {
        $group = DistributorTemplateGroup::create([
            'name' => 'KFTD'.$this->suffix,
            'skip_nonpositive_qty' => true,
            'column_map' => [
                'Tanggal' => 'Periode',
                'ID DISTRIBUTOR' => ['parts' => [['column' => 'Name Plant']], 'nospace' => true, 'upper' => true],
                'Distributor Item Name' => 'Material Description',
                'Quantity' => 'Stock Unrestricted',
                'Satuan' => 'UoM',
                'ED' => 'SLED/BBD',
                'Batch No' => 'Supplier Batch Number',
            ],
        ]);

        $data = [
            ['Periode', 'Name Plant', 'Material Description', 'Stock Unrestricted', 'UoM', 'SLED/BBD', 'Supplier Batch Number'],
            ['22/09/2026', 'KFTD Bandung', 'SUSU 400G', '12', 'PCS', '31/12/2027', 'SB-1'],
            ['22/09/2026', 'KFTD Bandung', 'SUSU 900G', '0', 'PCS', '31/12/2027', 'SB-2'],
        ];

        [$resolved, $reader] = $this->read($data, $group);
        $this->assertTrue($resolved['ok']);

        $this->assertSame('KFTDBANDUNG', $reader->distributorCode($data[1]));
        $this->assertFalse($reader->shouldSkip($data[1]));
        $this->assertTrue($reader->shouldSkip($data[2]));
    }

    public function test_periode_bulanan_memakai_akhir_bulan(): void
    {
        $group = DistributorTemplateGroup::create([
            'name' => 'SDL'.$this->suffix,
            'date_mode' => DistributorTemplateGroup::DATE_MONTH,
            'default_batch' => 'NO-BATCH',
            'column_map' => [
                'Tanggal' => 'PERIODE',
                'ID DISTRIBUTOR' => ['parts' => [['column' => 'CABANG']], 'nospace' => true, 'upper' => true],
                'Distributor Item Name' => 'Nama Barang',
                'Quantity' => 'Qty',
                'Satuan' => 'Satuan',
            ],
        ]);

        $data = [
            ['PERIODE', 'CABANG', 'Nama Barang', 'Qty', 'Satuan'],
            ['202609', 'SDL Bogor', 'SUSU 400G', '31', 'PCS'],
        ];

        [$resolved, $reader] = $this->read($data, $group);
        $this->assertTrue($resolved['ok']);
        $this->assertSame('2026-09-30', $reader->tanggal($data[1]));
        $this->assertSame('SDLBOGOR', $reader->distributorCode($data[1]));
    }

    public function test_resep_diabaikan_bila_kolom_yang_dibutuhkannya_tidak_ada(): void
    {
        $group = DistributorTemplateGroup::create([
            'name' => 'UDC parsial'.$this->suffix,
            'column_map' => [
                'ID DISTRIBUTOR' => ['parts' => [['text' => 'UDC'], ['column' => 'CUST_NAME']]],
            ],
        ]);

        // CUST_NAME tidak ada di berkas ini; resep tidak boleh dianggap
        // terpenuhi hanya karena bagian teks tetapnya selalu tersedia.
        $data = [['Tanggal', 'Nama Barang', 'Qty']];

        $resolved = (new StockHeaderResolver)->resolve($data, $group);

        $this->assertFalse($resolved['ok']);
        $this->assertContains('ID DISTRIBUTOR', $resolved['missing']);
    }

    public function test_ed_bulan_tahun_memakai_tanggal_1(): void
    {
        // Dijalankan seolah hari ini tanggal 31: format yang tidak menyebut hari
        // pernah meluber ke bulan berikutnya karena hari berjalan ikut terpakai.
        \Carbon\Carbon::setTestNow('2026-01-31');

        $group = DistributorTemplateGroup::create([
            'name' => 'ED bulanan'.$this->suffix,
            'ed_format' => 'm/Y',
            'column_map' => [
                'Tanggal' => 'PERIODE',
                'ID DISTRIBUTOR' => 'CABANG',
                'Distributor Item Name' => 'Nama Barang',
                'Quantity' => 'Qty',
                'ED' => 'ED',
            ],
        ]);

        $data = [
            ['PERIODE', 'CABANG', 'Nama Barang', 'Qty', 'ED'],
            ['22/09/2026', 'SDL1', 'SUSU 400G', '10', '02/2027'],
            ['22/09/2026', 'SDL1', 'SUSU 900G', '10', '31/12/2027'],
        ];

        [, $reader] = $this->read($data, $group);

        $this->assertSame('2027-02-01', $reader->expiredDate($data[1]));
        // Nilai yang tidak cocok formatnya jatuh ke deteksi otomatis, bukan
        // diterima sebagai tanggal yang keliru.
        $this->assertSame('2027-12-31', $reader->expiredDate($data[2]));

        \Carbon\Carbon::setTestNow();
    }

    public function test_format_tanggal_menentukan_cara_bacanya(): void
    {
        $group = DistributorTemplateGroup::create([
            'name' => 'Bulanan'.$this->suffix,
            'date_format' => 'm/Y',
            'column_map' => [
                'Tanggal' => 'PERIODE',
                'ID DISTRIBUTOR' => 'CABANG',
                'Distributor Item Name' => 'Nama Barang',
                'Quantity' => 'Qty',
            ],
        ]);

        // Format bulan/tahun sudah cukup menyatakan bahwa ini periode bulanan;
        // tidak ada setelan terpisah yang perlu dinyalakan.
        $this->assertSame(DistributorTemplateGroup::DATE_MONTH, $group->dateMode());

        $data = [
            ['PERIODE', 'CABANG', 'Nama Barang', 'Qty'],
            ['09/2026', 'SDL1', 'SUSU 400G', '10'],
        ];

        [, $reader] = $this->read($data, $group);

        // Tanggal snapshot memakai AKHIR bulan, kebalikan dari ED.
        $this->assertSame('2026-09-30', $reader->tanggal($data[1]));
    }

    public function test_format_ditulis_dengan_notasi_ddmmyyyy(): void
    {
        $group = DistributorTemplateGroup::create([
            'name' => 'Notasi'.$this->suffix,
            // Notasi yang wajar ditulis orang, bukan lambang PHP.
            'date_format' => 'dd/mm/yyyy',
            'ed_format' => 'yyyy/mm',
            'column_map' => [
                'Tanggal' => 'PERIODE',
                'ID DISTRIBUTOR' => 'CABANG',
                'Distributor Item Name' => 'Nama Barang',
                'Quantity' => 'Qty',
                'ED' => 'ED',
            ],
        ]);

        $data = [
            ['PERIODE', 'CABANG', 'Nama Barang', 'Qty', 'ED'],
            ['22/09/2026', 'SDL1', 'SUSU 400G', '10', '2027/02'],
        ];

        [, $reader] = $this->read($data, $group);

        $this->assertSame('2026-09-22', $reader->tanggal($data[1]));
        $this->assertSame('2027-02-01', $reader->expiredDate($data[1]));
    }

    public function test_format_yang_tak_masuk_akal_ditolak(): void
    {
        $m = DistributorTemplateGroup::class;

        // Bulan dan tahun wajib ada; sisanya salah ketik.
        $this->assertFalse($m::formatIsValid('yyyy'));
        $this->assertFalse($m::formatIsValid('dd'));
        $this->assertFalse($m::formatIsValid('qq/xx'));

        $this->assertTrue($m::formatIsValid('dd/mm/yyyy'));
        $this->assertTrue($m::formatIsValid('DD/MM/YYYY'));
        // Notasi PHP yang sudah tersimpan sebelumnya tetap berlaku.
        $this->assertTrue($m::formatIsValid('d/m/Y'));

        $this->assertTrue($m::formatIsMonthOnly('mm/yyyy'));
        $this->assertFalse($m::formatIsMonthOnly('dd/mm/yyyy'));
    }

    public function test_kode_distributor_dipetakan_lewat_code_map(): void
    {
        $group = DistributorTemplateGroup::create([
            'name' => 'Kode Sendiri'.$this->suffix,
            'code_map' => ['D001' => 'SDLSURABAYA', 'd002' => 'sdlbogor'],
            'column_map' => [
                'ID DISTRIBUTOR' => 'CABANG',
                'Distributor Item Name' => 'Nama Barang',
                'Quantity' => 'Qty',
            ],
        ]);

        $data = [
            ['CABANG', 'Nama Barang', 'Qty'],
            ['D001', 'SUSU 400G', '10'],
            ['d002', 'SUSU 400G', '5'],
            ['KODE-LAIN', 'SUSU 400G', '3'],
        ];

        [, $reader] = $this->read($data, $group);

        // Kode alias dipetakan ke kode resmi, tanpa peduli besar-kecil huruf.
        $this->assertSame('SDLSURABAYA', $reader->distributorCode($data[1]));
        $this->assertSame('SDLBOGOR', $reader->distributorCode($data[2]));
        // Kode yang tidak ada di pemetaan diteruskan apa adanya.
        $this->assertSame('KODE-LAIN', $reader->distributorCode($data[3]));
    }
}
