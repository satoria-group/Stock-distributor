<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Membuat 3 akun contoh (admin / sales / logistik).
 *
 * Password TIDAK lagi di-hardcode. Urutan penentuannya:
 *  1. Nilai DEMO_USER_PASSWORD di .env, kalau diisi.
 *  2. Kalau kosong, dibuat acak dan ditampilkan sekali di terminal.
 *
 * Seeder ini idempotent: akun yang sudah ada TIDAK diubah passwordnya,
 * supaya menjalankan ulang db:seed tidak mengunci user yang sudah
 * mengganti passwordnya sendiri.
 */
class DemoUserSeeder extends Seeder
{
    /** Password bawaan lama yang pernah ter-commit di repo. */
    private const LEGACY_PASSWORD = 'satoria123';

    public function run(): void
    {
        if (app()->isProduction() && ! filter_var(env('SEED_DEMO_USERS', false), FILTER_VALIDATE_BOOL)) {
            $this->command?->warn('Environment production terdeteksi — DemoUserSeeder dilewati. Set SEED_DEMO_USERS=true bila memang disengaja.');

            return;
        }

        $password = (string) env('DEMO_USER_PASSWORD', '');
        $generated = false;

        if (trim($password) === '') {
            $password = Str::password(16);
            $generated = true;
        }

        $users = [
            ['name' => 'Admin Satoria', 'email' => 'admin@satoriagroup.co.id', 'role' => User::ROLE_ADMIN],
            ['name' => 'Sales Satoria', 'email' => 'sales@satoriagroup.co.id', 'role' => User::ROLE_SALES],
            ['name' => 'Logistik Satoria', 'email' => 'logistik@satoriagroup.co.id', 'role' => User::ROLE_LOGISTIK],
        ];

        $created = 0;
        $legacyFound = [];

        foreach ($users as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make($password),
                    'email_verified_at' => now(),
                ]
            );

            if ($user->wasRecentlyCreated) {
                $created++;
            } elseif (Hash::check(self::LEGACY_PASSWORD, $user->password)) {
                $legacyFound[] = $user->email;
            }

            if (! $user->hasRole($data['role'])) {
                $user->syncRoles([$data['role']]);
            }
        }

        if ($created > 0 && $generated) {
            $this->command?->warn("Password untuk {$created} akun baru di-generate acak: {$password}");
            $this->command?->warn('Catat sekarang — password ini tidak ditampilkan lagi. Atau set DEMO_USER_PASSWORD di .env sebelum menjalankan seeder.');
        }

        if ($legacyFound !== []) {
            $this->command?->error('PERINGATAN KEAMANAN: akun berikut masih memakai password bawaan lama yang pernah ter-commit di repo:');
            foreach ($legacyFound as $email) {
                $this->command?->error("  - {$email}");
            }
            $this->command?->error('Ganti password akun tersebut sekarang lewat menu Manajemen User.');
        }
    }
}
