<?php

namespace Tests\Feature;

use App\Models\DistributorTemplateGroup;
use App\Support\StockHeaderResolver;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Berkas distributor tidak wajib mengikuti template baku: judul kolom umum
 * dikenali otomatis, judul khas dipetakan lewat Grup Template.
 */
class TemplateGroupMappingTest extends TestCase
{
    use DatabaseTransactions;

    public function test_judul_kolom_umum_dikenali_tanpa_grup(): void
    {
        $data = [
            ['LAPORAN STOK HARIAN', null, null, null],
            [null, null, null, null],
            ['TGL', 'KODE CABANG', 'NAMA BARANG', 'STOK AKHIR', 'SATUAN', 'TGL KADALUARSA', 'NO LOT'],
            ['22/09/2026', 'UDC-01', 'SUSU 400G', '120', 'PCS', '31/12/2027', 'B1'],
        ];

        $resolved = (new StockHeaderResolver)->resolve($data);

        $this->assertTrue($resolved['ok']);
        // Kop surat di atas tabel tidak boleh membuat baris header salah tebak.
        $this->assertSame(2, $resolved['header_row']);
        $this->assertSame(3, $resolved['col']['Quantity']);
        $this->assertSame(6, $resolved['col']['Batch No']);
    }

    public function test_mapping_grup_mengalahkan_tebakan_sinonim(): void
    {
        $group = DistributorTemplateGroup::create([
            'name' => 'UDC uji '.uniqid(),
            'column_map' => [
                'Tanggal' => 'TGL',
                'ID DISTRIBUTOR' => 'CAB',
                'Distributor Item Name' => 'NAMA BARANG',
                'Quantity' => 'SISA',
            ],
        ]);

        // 'QTY RETUR' adalah sinonim yang menggoda untuk Quantity; mapping grup
        // harus menahannya supaya kolom 'SISA' yang terpakai.
        $data = [['TGL', 'CAB', 'NAMA BARANG', 'QTY RETUR', 'SISA']];

        $resolved = (new StockHeaderResolver)->resolve($data, $group);

        $this->assertTrue($resolved['ok']);
        $this->assertSame(4, $resolved['col']['Quantity']);
        $this->assertSame(1, $resolved['col']['ID DISTRIBUTOR']);
    }

    public function test_kolom_wajib_yang_tak_terpetakan_melaporkan_nama_kolomnya(): void
    {
        $data = [['TGL', 'NAMA BARANG', 'STOK AKHIR']];

        $resolved = (new StockHeaderResolver)->resolve($data);

        $this->assertFalse($resolved['ok']);
        $this->assertSame(['ID DISTRIBUTOR'], $resolved['missing']);
        $this->assertStringContainsString('Grup Template', (string) $resolved['error']);
    }

    public function test_baris_header_paksaan_dari_grup_dipatuhi(): void
    {
        $group = DistributorTemplateGroup::create([
            'name' => 'KFTD uji '.uniqid(),
            'header_row' => 2,
            // Grup selalu memetakan manual, tidak ada tebakan sinonim — jadi
            // baris headernya sendiri juga harus dipetakan penuh di sini agar
            // baris paksaan ini benar-benar bisa dibaca.
            'column_map' => [
                'Tanggal' => 'TGL',
                'ID DISTRIBUTOR' => 'KODE CABANG',
                'Distributor Item Name' => 'NAMA BARANG',
                'Quantity' => 'STOK AKHIR',
            ],
        ]);

        $data = [
            ['Tanggal', 'ID DISTRIBUTOR', 'Distributor Item Name', 'Quantity'],
            ['TGL', 'KODE CABANG', 'NAMA BARANG', 'STOK AKHIR'],
        ];

        $resolved = (new StockHeaderResolver)->resolve($data, $group);

        $this->assertTrue($resolved['ok']);
        $this->assertSame(1, $resolved['header_row']);
    }
}
