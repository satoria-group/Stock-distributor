<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Tests\TestCase;

/**
 * Menguji penjaga yang mencegah test mengosongkan database kerja.
 *
 * Sengaja menguji fungsi keputusannya secara langsung, BUKAN dengan membuat
 * test yang benar-benar memakai RefreshDatabase — menjalankan test semacam itu
 * persis merupakan bahaya yang hendak dicegah.
 */
class DatabaseWipeGuardTest extends PHPUnitTestCase
{
    private function traits(string ...$names): array
    {
        return array_combine($names, $names);
    }

    public function test_blocks_refresh_database_on_a_working_database(): void
    {
        $this->assertSame(
            'RefreshDatabase',
            TestCase::blockingDestructiveTrait($this->traits(RefreshDatabase::class), 'crm')
        );
    }

    public function test_blocks_database_migrations_and_truncation_too(): void
    {
        $this->assertSame(
            'DatabaseMigrations',
            TestCase::blockingDestructiveTrait($this->traits(DatabaseMigrations::class), 'stock_distributor')
        );

        $this->assertSame(
            'DatabaseTruncation',
            TestCase::blockingDestructiveTrait($this->traits(DatabaseTruncation::class), 'produksi')
        );
    }

    public function test_allows_transactional_tests_on_any_database(): void
    {
        $this->assertNull(
            TestCase::blockingDestructiveTrait($this->traits(DatabaseTransactions::class), 'crm'),
            'DatabaseTransactions aman: seluruh perubahan di-rollback.'
        );
    }

    public function test_allows_no_database_traits_at_all(): void
    {
        $this->assertNull(TestCase::blockingDestructiveTrait([], 'crm'));
    }

    /**
     * Database yang memang ditujukan untuk test tetap boleh dihapus-isi,
     * supaya penjaga ini tidak menghalangi cara kerja yang benar.
     */
    public function test_allows_destructive_traits_on_a_designated_test_database(): void
    {
        foreach (['stock_distributor_test', 'test_crm', 'crm_testing', ':memory:'] as $db) {
            $this->assertNull(
                TestCase::blockingDestructiveTrait($this->traits(RefreshDatabase::class), $db),
                "Database test '{$db}' seharusnya diizinkan."
            );
        }
    }

    public function test_does_not_mistake_similar_names_for_test_databases(): void
    {
        foreach (['contest', 'latest_stock', 'protest'] as $db) {
            $this->assertSame(
                'RefreshDatabase',
                TestCase::blockingDestructiveTrait($this->traits(RefreshDatabase::class), $db),
                "Database '{$db}' BUKAN database test dan harus tetap diblokir."
            );
        }
    }
}
