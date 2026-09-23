<?php

namespace Database\Seeders;

use App\Models\DistributorTemplateGroup;
use Illuminate\Database\Seeder;

/**
 * Lima grup template yang berkasnya sudah diketahui bentuknya.
 *
 * Nilai di sini berasal dari daftar kolom yang diberikan tim operasional, jadi
 * dipakai sebagai TITIK AWAL, bukan kebenaran akhir: judul kolom bisa berbeda
 * ejaannya di berkas yang sesungguhnya. Setelah berkas contoh tiap grup
 * diunggah di halaman Grup Template, pratinjau akan menunjukkan kolom mana
 * yang belum cocok.
 *
 * Seeder ini aman dijalankan berulang: grup yang sudah ada TIDAK ditimpa,
 * supaya penyesuaian manual operator tidak hilang saat deploy berikutnya.
 */
class DistributorTemplateGroupSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->groups() as $group) {
            DistributorTemplateGroup::firstOrCreate(
                ['name' => $group['name']],
                $group
            );
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function groups(): array
    {
        return [
            [
                'name' => 'UDC',
                'notes' => 'Tanggal dirakit dari tiga kolom (TGL/BLN/TAHUN); kode cabang = "UDC" + CUST_NAME.',
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
            ],
            [
                'name' => 'GMP',
                'notes' => 'Tidak punya kolom ED maupun Batch — memakai batch pengganti NO-BATCH.',
                'default_batch' => 'NO-BATCH',
                'column_map' => [
                    'Tanggal' => 'PERIODE',
                    'ID DISTRIBUTOR' => ['parts' => [['column' => 'DISTRIBUTOR']], 'nospace' => true, 'upper' => true],
                    'Distributor Item Name' => 'Nama Brg',
                    'Quantity' => 'Saldo Akhir',
                    'Satuan' => 'Sat Trans',
                ],
            ],
            [
                'name' => 'SDL',
                'notes' => 'Tidak punya kolom ED maupun Batch — memakai batch pengganti NO-BATCH.',
                'default_batch' => 'NO-BATCH',
                'column_map' => [
                    'Tanggal' => 'PERIODE',
                    'ID DISTRIBUTOR' => ['parts' => [['column' => 'CABANG']], 'nospace' => true, 'upper' => true],
                    'Distributor Item Name' => 'Nama Barang',
                    'Quantity' => 'Qty',
                    'Satuan' => 'Satuan',
                ],
            ],
            [
                'name' => 'MAM',
                'notes' => 'Kode cabang = kolom Distributor + "JAKARTA". Tanpa ED dan Batch.',
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
            ],
            [
                'name' => 'KFTD',
                'notes' => 'Mengirim seluruh isi gudang termasuk stok nol; baris berstok <= 0 diabaikan.',
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
            ],
        ];
    }
}
