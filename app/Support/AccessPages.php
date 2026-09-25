<?php

namespace App\Support;

use App\Models\User;

/**
 * Daftar halaman beserta permission-nya — satu-satunya sumber yang dipakai
 * halaman Hak Akses Role dan pengalihan halaman awal setelah login.
 *
 * 'view'   = boleh membuka halaman
 * 'manage' = boleh menambah/mengubah/menghapus di halaman itu (null bila
 *            halamannya tidak punya aksi terpisah)
 */
class AccessPages
{
    /** @return array<int, array{key: string, label: string, route: string, view: string, manage: ?string}> */
    public static function all(): array
    {
        return [
            ['key' => 'dashboard', 'label' => 'Dashboard', 'route' => 'dashboard', 'view' => 'dashboard.view', 'manage' => null],
            ['key' => 'distributors', 'label' => 'Master Distributor', 'route' => 'distributors.index', 'view' => 'distributors.view', 'manage' => 'distributors.manage'],
            ['key' => 'template-groups', 'label' => 'Format Berkas Excel', 'route' => 'template-groups.index', 'view' => 'template-groups.view', 'manage' => 'template-groups.manage'],
            ['key' => 'netsuite-items', 'label' => 'Master Produk (Netsuite)', 'route' => 'netsuite-items.index', 'view' => 'netsuite-items.view', 'manage' => 'netsuite-items.manage'],
            // Tab di halaman Master Produk.
            ['key' => 'unit-conversions', 'label' => 'Konversi Satuan (tab di Master Produk)', 'route' => 'netsuite-items.index', 'view' => 'unit-conversions.view', 'manage' => 'unit-conversions.manage'],
            ['key' => 'distributor-items', 'label' => 'Mapping Item Distributor', 'route' => 'distributor-items.index', 'view' => 'distributor-items.view', 'manage' => 'distributor-items.manage'],
            ['key' => 'emails', 'label' => 'Inbox Email Distributor', 'route' => 'emails.index', 'view' => 'emails.view', 'manage' => null],
            ['key' => 'stock-upload', 'label' => 'Upload Stock Harian', 'route' => 'stock.upload', 'view' => 'stock.upload', 'manage' => null],
            ['key' => 'stock-history', 'label' => 'Riwayat Stok', 'route' => 'stock.history', 'view' => 'stock.view', 'manage' => null],
            ['key' => 'users', 'label' => 'Manajemen User & Hak Akses', 'route' => 'users.index', 'view' => 'users.manage', 'manage' => null],
        ];
    }

    /** @return array<int, string> */
    public static function permissions(): array
    {
        $out = [];
        foreach (self::all() as $page) {
            $out[] = $page['view'];
            if ($page['manage']) {
                $out[] = $page['manage'];
            }
        }

        return array_values(array_unique($out));
    }

    /** Halaman pertama yang boleh dibuka user; dipakai sebagai halaman awal. */
    public static function homeFor(?User $user): ?string
    {
        foreach (self::all() as $page) {
            if ($user?->can($page['view'])) {
                return route($page['route']);
            }
        }

        return null;
    }
}
