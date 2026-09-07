<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'Admin Satoria', 'email' => 'admin@satoriagroup.co.id', 'role' => User::ROLE_ADMIN],
            ['name' => 'Sales Satoria', 'email' => 'sales@satoriagroup.co.id', 'role' => User::ROLE_SALES],
            ['name' => 'Logistik Satoria', 'email' => 'logistik@satoriagroup.co.id', 'role' => User::ROLE_LOGISTIK],
        ];

        foreach ($users as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => bcrypt('satoria123'),
                    'email_verified_at' => now(),
                ]
            );

            if (! $user->hasRole($data['role'])) {
                $user->syncRoles([$data['role']]);
            }
        }
    }
}
