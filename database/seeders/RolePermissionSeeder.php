<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Sesuai matriks akses yang disetujui:
     *  - admin    : CRUD semua modul (master data, upload, dashboard, user & role)
     *  - sales    : hanya dashboard (read-only)
     *  - logistik : CRUD upload stock harian + lihat mapping + dashboard
     */
    public function run(): void
    {
        $permissions = \App\Support\AccessPages::permissions();

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $admin = Role::firstOrCreate(['name' => User::ROLE_ADMIN, 'guard_name' => 'web']);
        $admin->syncPermissions($permissions);

        $sales = Role::firstOrCreate(['name' => User::ROLE_SALES, 'guard_name' => 'web']);
        $sales->syncPermissions(['dashboard.view']);

        $logistik = Role::firstOrCreate(['name' => User::ROLE_LOGISTIK, 'guard_name' => 'web']);
        $logistik->syncPermissions([
            'distributor-items.view',
            'template-groups.view',
            'emails.view',
            'stock.view',
            'stock.upload',
            'dashboard.view',
        ]);
    }
}
