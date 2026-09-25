<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

// Permission baru untuk halaman yang sebelumnya dijaga lewat nama role
// (Inbox Email) atau belum ada (Konversi Satuan). Hak akses awalnya disamakan
// dengan perilaku sebelumnya.
return new class extends Migration
{
    private const GRANTS = [
        'emails.view' => [User::ROLE_ADMIN, User::ROLE_LOGISTIK],
        'unit-conversions.view' => [User::ROLE_ADMIN],
        'unit-conversions.manage' => [User::ROLE_ADMIN],
    ];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::GRANTS as $name => $roles) {
            $permission = Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);

            foreach ($roles as $roleName) {
                Role::where('name', $roleName)->where('guard_name', 'web')->first()?->givePermissionTo($permission);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::whereIn('name', array_keys(self::GRANTS))->where('guard_name', 'web')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
