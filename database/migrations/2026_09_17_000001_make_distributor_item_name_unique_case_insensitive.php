<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Menyamakan index unik dengan cara kode mencari item.
 *
 * Index lama unik pada (distributor_id, item_name) — PERSIS, peka huruf
 * besar-kecil dan spasi. Padahal seluruh kode mencari item secara
 * case-insensitive: `whereRaw('LOWER(TRIM(item_name)) = ?')` dan
 * `keyBy(mb_strtolower(...))`.
 *
 * Akibat ketidakcocokan itu: "Dextrose 5%" dan "DEXTROSE 5%" lolos sebagai dua
 * baris — database menganggapnya beda, kode menganggapnya sama. Blok
 * `catch (UniqueConstraintViolationException)` yang dipasang untuk mencegahnya
 * tidak pernah terpicu karena constraint-nya memang tidak dilanggar, dan
 * `$knownItems->get($key)` jadi tidak menentu (baris terakhir yang menang).
 *
 * Index baru mencakup baris ter-soft-delete, sama seperti index lama, karena
 * kode memang mencari dengan withTrashed() lalu me-restore.
 */
return new class extends Migration
{
    private const OLD_INDEX = 'distributor_items_distributor_id_item_name_unique';

    private const NEW_INDEX = 'distributor_items_distributor_id_item_name_ci_unique';

    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            // Index fungsional adalah fitur PostgreSQL. Di driver lain biarkan
            // index lama; aplikasi ini memang hanya dijalankan di PostgreSQL.
            return;
        }

        $this->guardAgainstExistingDuplicates();

        // $table->unique() milik Laravel membuat CONSTRAINT, bukan sekadar
        // index — jadi harus dilepas lewat ALTER TABLE, bukan DROP INDEX.
        DB::statement('ALTER TABLE distributor_items DROP CONSTRAINT IF EXISTS '.self::OLD_INDEX);
        DB::statement('DROP INDEX IF EXISTS '.self::OLD_INDEX);

        DB::statement(
            'CREATE UNIQUE INDEX '.self::NEW_INDEX.
            ' ON distributor_items (distributor_id, lower(trim(item_name)))'
        );
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS '.self::NEW_INDEX);

        if (! $this->plainDuplicatesExist()) {
            // Dikembalikan sebagai constraint, persis seperti bentuk aslinya.
            DB::statement(
                'ALTER TABLE distributor_items ADD CONSTRAINT '.self::OLD_INDEX.
                ' UNIQUE (distributor_id, item_name)'
            );
        }
    }

    /**
     * Berhenti dengan pesan jelas daripada gagal dengan error Postgres mentah,
     * atau lebih buruk: diam-diam menggabungkan data milik orang lain.
     * Penggabungan duplikat adalah keputusan bisnis, bukan urusan migration.
     */
    private function guardAgainstExistingDuplicates(): void
    {
        // Heredoc: separator string_agg harus pakai kutip TUNGGAL (di Postgres
        // kutip ganda berarti identifier, bukan teks).
        $dups = DB::select(<<<'SQL'
            SELECT distributor_id,
                   lower(trim(item_name))     AS norm,
                   count(*)                   AS n,
                   string_agg(id::text, ', ') AS ids
            FROM distributor_items
            GROUP BY distributor_id, lower(trim(item_name))
            HAVING count(*) > 1
        SQL);

        if ($dups === []) {
            return;
        }

        $preview = collect($dups)->take(10)
            ->map(fn ($d) => "  - distributor_id={$d->distributor_id} \"{$d->norm}\" (".$d->n.' baris: id '.$d->ids.')')
            ->implode("\n");

        throw new \RuntimeException(
            "Migration dihentikan: ditemukan ".count($dups)." nama item yang duplikat bila huruf besar-kecil diabaikan.\n".
            "Index unik case-insensitive tidak bisa dibuat sebelum duplikat ini digabungkan secara manual.\n".
            $preview."\n".
            "Gabungkan/hapus salah satunya (perhatikan stock_entries yang menunjuk ke id tersebut), lalu jalankan ulang migration."
        );
    }

    private function plainDuplicatesExist(): bool
    {
        return DB::select('
            SELECT 1 FROM distributor_items
            GROUP BY distributor_id, item_name
            HAVING count(*) > 1 LIMIT 1
        ') !== [];
    }
};
