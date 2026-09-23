<?php

namespace App\Support;

/**
 * Definisi kolom kanonik pada berkas stok distributor.
 *
 * "Kanonik" di sini berarti nama kolom yang dipakai di dalam kode
 * ($col['Quantity'], dst.) — BUKAN judul kolom yang tertulis di berkas Excel
 * milik distributor. Sebuah distributor boleh menulis 'Qty', 'Jumlah', atau
 * 'STOK AKHIR'; StockHeaderResolver-lah yang memetakannya ke kanonik ini,
 * baik lewat daftar sinonim bawaan di bawah maupun lewat mapping manual yang
 * disimpan pada DistributorTemplateGroup.
 *
 * Menambah sinonim di sini menguntungkan SEMUA distributor sekaligus; mapping
 * per grup dipakai hanya untuk judul yang benar-benar khas satu grup.
 */
class StockTemplateColumns
{
    /**
     * @return array<string, array{label: string, required: bool, synonyms: array<int, string>, example: string}>
     *         key = nama kanonik yang dipakai kode pemanggil
     */
    public static function definitions(): array
    {
        return [
            'Tanggal' => [
                'label' => 'Tanggal Snapshot',
                'required' => true,
                'example' => '22/09/2026',
                'synonyms' => [
                    'tanggal', 'tgl', 'date', 'tanggal stock', 'tanggal stok',
                    'tanggal laporan', 'periode', 'tgl stock', 'tgl stok',
                    'stock date', 'report date', 'posting date',
                ],
            ],
            'ID DISTRIBUTOR' => [
                'label' => 'Kode Distributor',
                'required' => true,
                'example' => 'IGMPUSAT',
                'synonyms' => [
                    'id distributor', 'iddistributor', 'kode distributor',
                    'distributor code', 'distributor id', 'kode dist',
                    'kode cabang', 'branch code', 'customer code', 'kode customer',
                ],
            ],
            'Distributor Item Name' => [
                'label' => 'Nama Item Distributor',
                'required' => true,
                'example' => 'SUSU BUBUK 400G',
                'synonyms' => [
                    'distributor item name', 'item name', 'nama item',
                    'nama barang', 'nama produk', 'item', 'produk', 'product',
                    'product name', 'deskripsi barang', 'deskripsi', 'description',
                    'material description', 'barang',
                ],
            ],
            'Quantity' => [
                'label' => 'Quantity',
                'required' => true,
                'example' => '120',
                'synonyms' => [
                    'quantity', 'qty', 'jumlah', 'stok', 'stock', 'saldo',
                    'saldo akhir', 'stok akhir', 'stock akhir', 'qty akhir',
                    'ending stock', 'on hand', 'onhand', 'qty on hand',
                ],
            ],
            'Satuan' => [
                'label' => 'Satuan (UOM)',
                'required' => false,
                'example' => 'PCS',
                'synonyms' => [
                    'satuan', 'uom', 'unit', 'units', 'unit of measure',
                    'satuan unit', 'kemasan',
                ],
            ],
            'ED' => [
                'label' => 'Expired Date',
                'required' => false,
                'example' => '31/12/2027',
                'synonyms' => [
                    'ed', 'exp', 'expired', 'expire', 'expired date', 'expiry',
                    'expiry date', 'expire date', 'tgl expired', 'tanggal expired',
                    'tgl kadaluarsa', 'tanggal kadaluarsa', 'kadaluarsa',
                    'best before', 'bb date',
                ],
            ],
            'Batch No' => [
                'label' => 'Batch / Lot',
                'required' => false,
                'example' => 'B2409A',
                'synonyms' => [
                    'batch no', 'batch', 'no batch', 'nomor batch', 'batch number',
                    'lot', 'lot no', 'no lot', 'lot number', 'batch lot',
                ],
            ],
        ];
    }

    /** @return array<int, string> nama kanonik yang wajib ada */
    public static function required(): array
    {
        return array_keys(array_filter(self::definitions(), fn ($d) => $d['required']));
    }

    /** @return array<int, string> semua nama kanonik */
    public static function all(): array
    {
        return array_keys(self::definitions());
    }

    /**
     * Bentuk perbandingan sebuah judul kolom.
     *
     * Dibuat seagresif mungkin supaya 'ID DISTRIBUTOR', 'Id_Distributor',
     * dan ' id  distributor. ' semuanya jatuh ke string yang sama. Semua
     * pembandingan judul — sinonim bawaan maupun mapping tersimpan — WAJIB
     * lewat sini, jangan pernah membandingkan judul mentah.
     */
    public static function normalize(?string $header): string
    {
        $h = mb_strtolower(trim((string) $header));
        $h = preg_replace('/[^a-z0-9]+/u', ' ', $h) ?? '';
        $h = preg_replace('/\s+/', ' ', $h) ?? '';

        return trim($h);
    }
}
