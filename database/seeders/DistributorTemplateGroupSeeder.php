<?php

namespace Database\Seeders;

use App\Models\DistributorGroup;
use App\Models\DistributorTemplateGroup;
use Illuminate\Database\Seeder;

/**
 * Enam bentuk berkas yang sungguh beredar, disusun dari berkas aslinya.
 *
 * Nilai di sini bukan tebakan: tiap resep diuji langsung terhadap berkas nyata
 * milik distributor yang bersangkutan (lihat TemplateGroupRealFileTest), jadi
 * yang tertulis di bawah adalah bentuk berkas sebagaimana adanya — termasuk
 * kejanggalannya.
 *
 * Seeder ini aman dijalankan berulang: grup yang sudah ada TIDAK ditimpa,
 * supaya penyesuaian manual operator tidak hilang saat deploy berikutnya.
 */
class DistributorTemplateGroupSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->groups() as $definition) {
            $usedBy = $definition['used_by'] ?? null;
            unset($definition['used_by']);

            $group = DistributorTemplateGroup::firstOrCreate(
                ['name' => $definition['name']],
                $definition
            );

            // Bentuk berkas menempel pada grup usaha, bukan pada tiap cabang.
            if ($usedBy) {
                DistributorGroup::where('name', $usedBy)
                    ->whereNull('template_group_id')
                    ->update(['template_group_id' => $group->id]);
            }
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function groups(): array
    {
        return [
            [
                'name' => 'UDC',
                'used_by' => 'UDC',
                'notes' => 'Sheet "Per Item Per Cabang". Tanggal dirakit dari TGL/BLN/TAHUN; ED berbentuk 2027/11.',
                'sheet_name' => 'Per Item Per Cabang',
                'ed_format' => 'yyyy/mm',
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
                'name' => 'KFTD',
                'used_by' => 'KFTD',
                'notes' => 'Tidak punya kolom tanggal — diambil dari nama berkas. Mengirim seluruh isi gudang termasuk stok nol.',
                'sheet_name' => 'Sheet1',
                'skip_nonpositive_qty' => true,
                'column_map' => [
                    'Tanggal' => ['parts' => [['file' => true]]],
                    'ID DISTRIBUTOR' => ['parts' => [['column' => 'Name Plant']], 'nospace' => true, 'upper' => true],
                    'Distributor Item Name' => 'Material Description',
                    'Quantity' => 'Stock Unrestricted',
                    'Satuan' => 'UoM',
                    'ED' => 'SLED/BBD',
                    'Batch No' => 'Supplier Batch Number',
                ],
            ],
            [
                'name' => 'PPI',
                'used_by' => 'PPI',
                'notes' => 'Pivot table: cabang & nama produk hanya terisi di baris pertama tiap blok, dan ada baris Total. Tanggal ada di judul laporan (A3). Tanpa kolom satuan.',
                'sheet_name' => 'PER CABANG',
                'header_row' => 6,
                'fill_down' => ['ID DISTRIBUTOR', 'Distributor Item Name'],
                'column_map' => [
                    'Tanggal' => ['parts' => [['cell' => 'A3']]],
                    'ID DISTRIBUTOR' => ['parts' => [['column' => 'warehouse']], 'nospace' => true, 'upper' => true],
                    'Distributor Item Name' => 'product_name',
                    'Quantity' => 'Sum of qty',
                    'ED' => 'exp_date',
                    'Batch No' => 'batch_id',
                ],
            ],
            [
                'name' => 'SDL',
                'used_by' => 'SDL',
                'notes' => 'Satu sheet per cabang (pola "SDL *"); cabang diambil dari nama sheet. Tanggal di sel B1 sheet pertama. Tanpa ED dan batch.',
                'sheet_name' => 'SDL *',
                'header_row' => 15,
                'default_batch' => 'NO-BATCH',
                'column_map' => [
                    'Tanggal' => ['parts' => [['cell' => 'B1']]],
                    'ID DISTRIBUTOR' => ['parts' => [['sheet' => true]], 'nospace' => true, 'upper' => true],
                    'Distributor Item Name' => 'Nama Barang',
                    'Quantity' => 'Qty',
                    'Satuan' => 'Satuan',
                ],
            ],
            [
                'name' => 'GMP',
                'used_by' => 'GMP',
                'notes' => 'Berkas satu cabang. Tanggal di sel A3 berupa rentang periode — dipakai tanggal akhirnya. Tanpa ED dan batch.',
                'sheet_name' => 'Sheet1',
                'header_row' => 5,
                'default_batch' => 'NO-BATCH',
                'column_map' => [
                    'Tanggal' => ['parts' => [['cell' => 'A3']]],
                    'ID DISTRIBUTOR' => ['parts' => [['text' => 'IGMPUSAT']]],
                    'Distributor Item Name' => 'Nama Brg',
                    'Quantity' => 'Saldo Akhir',
                    'Satuan' => 'Sat Trans',
                ],
            ],
            [
                'name' => 'MAM',
                'used_by' => 'MAM',
                'notes' => 'Berkas satu cabang, tanpa kolom tanggal maupun cabang — tanggal diambil dari nama berkas. Tanpa ED dan batch.',
                'sheet_name' => 'Sheet1',
                'default_batch' => 'NO-BATCH',
                'column_map' => [
                    'Tanggal' => ['parts' => [['file' => true]]],
                    'ID DISTRIBUTOR' => ['parts' => [['text' => 'MAMJAKARTA']]],
                    'Distributor Item Name' => 'Nama produk',
                    'Quantity' => 'Stock',
                    'Satuan' => '*Unit',
                ],
            ],
        ];
    }
}
