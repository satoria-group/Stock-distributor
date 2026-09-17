<?php

namespace Tests;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    /**
     * Nama database yang boleh dihapus-isi oleh test.
     *
     * phpunit.xml TIDAK menetapkan DB_CONNECTION/DB_DATABASE, sehingga test
     * berjalan pada database dari .env — saat ini database kerja yang berisi
     * data sungguhan. Selama semua test memakai DatabaseTransactions itu aman,
     * karena setiap perubahan di-rollback.
     *
     * Yang berbahaya adalah trait yang MENGHAPUS ISI database:
     * RefreshDatabase (migrate:fresh), DatabaseMigrations, dan
     * DatabaseTruncation. Satu test baru yang memakai salah satunya akan
     * mengosongkan database kerja tanpa peringatan apa pun.
     *
     * Penjaga di bawah menghentikan test tersebut SEBELUM sempat berjalan,
     * kecuali database yang sedang dipakai memang database test.
     */
    private const DESTRUCTIVE_TRAITS = [
        RefreshDatabase::class,
        DatabaseMigrations::class,
        DatabaseTruncation::class,
    ];

    /**
     * Pola nama database yang dianggap aman untuk dihapus-isi.
     */
    private const SAFE_DATABASE_PATTERNS = [
        '/(^|[_\-])test(ing)?([_\-]|$)/i',
        '/^:memory:$/',
    ];

    /**
     * Penjaga dipasang di setUpTraits(), BUKAN di setUp().
     *
     * setUp() berjalan sebelum aplikasi Laravel di-boot, sehingga config()
     * belum tersedia di sana. setUpTraits() berjalan setelah aplikasi siap
     * tetapi SEBELUM trait database menjalankan tugasnya — tepat di celah yang
     * dibutuhkan untuk menghentikan penghapusan data sebelum terjadi.
     */
    protected function setUpTraits()
    {
        $this->guardAgainstWipingRealDatabase();

        return parent::setUpTraits();
    }

    /**
     * Keputusan murni, tanpa menyentuh database — supaya bisa diuji langsung
     * tanpa harus benar-benar menjalankan test yang merusak.
     *
     * @param  array<string, string>  $traits  hasil class_uses_recursive()
     * @return string|null  nama trait yang menghalangi, atau null bila aman
     */
    public static function blockingDestructiveTrait(array $traits, string $database): ?string
    {
        $destructive = array_values(array_intersect(self::DESTRUCTIVE_TRAITS, $traits));

        if ($destructive === []) {
            return null;
        }

        foreach (self::SAFE_DATABASE_PATTERNS as $pattern) {
            if (preg_match($pattern, $database)) {
                return null;
            }
        }

        return class_basename($destructive[0]);
    }

    private function guardAgainstWipingRealDatabase(): void
    {
        $database = (string) (config('database.connections.'.config('database.default').'.database') ?? '');

        $traitName = self::blockingDestructiveTrait(class_uses_recursive(static::class), $database);

        if ($traitName === null) {
            return;
        }

        $this->fail(
            "BERHENTI: ".static::class." memakai {$traitName}, yang MENGHAPUS ISI database.\n".
            "Database yang sedang aktif adalah '{$database}' — bukan database test.\n\n".
            "Menjalankan test ini akan mengosongkan data sungguhan.\n\n".
            "Pilih salah satu:\n".
            "  1. Pakai trait DatabaseTransactions (seperti test lain di proyek ini), atau\n".
            "  2. Arahkan test ke database khusus, misalnya dengan menambahkan pada phpunit.xml:\n".
            "       <env name=\"DB_DATABASE\" value=\"stock_distributor_test\"/>\n".
            "     lalu buat database tersebut dan jalankan `php artisan migrate`."
        );
    }
}
