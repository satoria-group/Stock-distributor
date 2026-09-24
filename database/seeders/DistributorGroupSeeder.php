<?php

namespace Database\Seeders;

use App\Models\Distributor;
use App\Models\DistributorGroup;
use App\Models\DistributorTemplateGroup;
use Illuminate\Database\Seeder;

/**
 * Grup usaha awal, dibentuk dari kenyataan kode distributor yang sudah ada.
 *
 * Isi tabel distributor memperlihatkan segelintir grup besar bercabang banyak
 * dan puluhan distributor tunggal. Grup besar itulah yang didaftarkan di sini;
 * distributor tunggal sengaja dibiarkan tanpa grup dan tampil sebagai
 * "Lainnya" di dashboard — memaksa tiap satu punya grup sendiri hanya akan
 * membuat filter dashboard sepanjang daftar distributornya.
 *
 * Awalan kode dipakai SEKALI di sini untuk mengisi keanggotaan awal. Setelah
 * itu keanggotaan sepenuhnya ditentukan kolom distributor_group_id — sistem
 * tidak pernah lagi menebak dari kode.
 *
 * Aman dijalankan berulang: grup yang sudah ada tidak ditimpa, dan distributor
 * yang sudah punya grup tidak dipindahkan.
 */
class DistributorGroupSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->groups() as $index => $definition) {
            $group = DistributorGroup::firstOrCreate(
                ['name' => $definition['name']],
                [
                    'color' => $definition['color'],
                    'sort_order' => $index,
                    'notes' => $definition['notes'] ?? null,
                    'template_group_id' => $this->templateId($definition['template'] ?? null),
                ]
            );

            // Keanggotaan awal: distributor berawalan kode ini yang BELUM
            // punya grup. Yang sudah ditetapkan operator tidak diganggu.
            foreach ($definition['prefixes'] as $prefix) {
                Distributor::whereNull('distributor_group_id')
                    ->where('distributor_code', 'ilike', $prefix.'%')
                    ->update(['distributor_group_id' => $group->id]);
            }
        }

        $this->liftTemplateToGroups();
    }

    private function templateId(?string $name): ?int
    {
        return $name ? DistributorTemplateGroup::where('name', $name)->value('id') : null;
    }

    /**
     * Naikkan bentuk berkas dari tiap cabang ke grup usahanya.
     *
     * Sebelum ada grup usaha, template ditempelkan satu per satu ke cabang.
     * Setelah dinaikkan, mengubah template UDC cukup sekali dan berlaku untuk
     * seluruh cabangnya — kolom di cabang dikosongkan supaya tidak diam-diam
     * menimpa perubahan itu di kemudian hari.
     */
    private function liftTemplateToGroups(): void
    {
        foreach (DistributorGroup::whereNotNull('template_group_id')->get() as $group) {
            Distributor::where('distributor_group_id', $group->id)
                ->where('template_group_id', $group->template_group_id)
                ->update(['template_group_id' => null]);
        }
    }

    /** @return array<int, array{name: string, color: string, prefixes: array<int, string>, template?: string, notes?: string}> */
    private function groups(): array
    {
        return [
            ['name' => 'KFTD', 'color' => '#3b82f6', 'prefixes' => ['KFTD', 'KFT'], 'template' => 'KFTD',
                'notes' => 'Kimia Farma Trading & Distribution'],
            ['name' => 'RNI', 'color' => '#a855f7', 'prefixes' => ['RNI'],
                'notes' => 'Rajawali Nusantara Indonesia'],
            ['name' => 'PPI', 'color' => '#f97316', 'prefixes' => ['PPI'],
                'notes' => 'Perusahaan Perdagangan Indonesia'],
            ['name' => 'GMP', 'color' => '#84cc16', 'prefixes' => ['IGM', 'GMP'], 'template' => 'GMP',
                'notes' => 'Kode cabangnya berawalan IGM, bukan GMP'],
            ['name' => 'TSJ', 'color' => '#06b6d4', 'prefixes' => ['TSJ']],
            ['name' => 'UDC', 'color' => '#ec4899', 'prefixes' => ['UDC'], 'template' => 'UDC',
                'notes' => 'United Dico Citas'],
            ['name' => 'SAI', 'color' => '#eab308', 'prefixes' => ['SAI']],
            ['name' => 'SDL', 'color' => '#14b8a6', 'prefixes' => ['SDL'], 'template' => 'SDL'],
            ['name' => 'MAM', 'color' => '#ef4444', 'prefixes' => ['MAM'], 'template' => 'MAM'],
        ];
    }
}
