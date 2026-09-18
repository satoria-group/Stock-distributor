<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Ubah grain snapshot stok dari per-item menjadi per-BATCH.
 *
 * Kunci lama (tanggal, distributor_item_id) hanya mengizinkan SATU baris per
 * item per tanggal. Itulah sebabnya importer harus melebur beberapa batch
 * menjadi satu baris: kuantitas dijumlah, nomor batch disambung koma, dan ED
 * diambil yang paling awal.
 *
 * Peleburan itu merusak data: stok ber-ED 2029 ikut ditandai mendekati
 * kedaluwarsa hanya karena satu batch lain pada item yang sama ber-ED 2028,
 * dan ketertelusuran batch untuk penarikan produk hilang.
 *
 * COALESCE dipakai karena pada index unik PostgreSQL, NULL selalu dianggap
 * BERBEDA dari NULL. Tanpa itu, beberapa baris ber-batch kosong tetap bisa
 * masuk diam-diam — persis duplikat yang hendak dicegah.
 */
return new class extends Migration
{
    private const OLD_KEY = 'stock_entries_snapshot_key';

    private const NEW_KEY = 'stock_entries_snapshot_batch_key';

    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $this->guardAgainstExistingDuplicates();

        // $table->unique() milik Laravel membuat CONSTRAINT, bukan index biasa.
        DB::statement('ALTER TABLE stock_entries DROP CONSTRAINT IF EXISTS '.self::OLD_KEY);
        DB::statement('DROP INDEX IF EXISTS '.self::OLD_KEY);

        DB::statement(
            'CREATE UNIQUE INDEX '.self::NEW_KEY.
            " ON stock_entries (tanggal, distributor_item_id, COALESCE(batch_no, ''))"
        );
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS '.self::NEW_KEY);

        // Hanya bisa dikembalikan bila tidak ada item yang punya >1 batch pada
        // tanggal yang sama — dan itu justru data yang sengaja dibuat mungkin
        // oleh migration ini. Jangan hapus data untuk memaksakan rollback.
        $conflict = DB::select('
            SELECT 1 FROM stock_entries
            GROUP BY tanggal, distributor_item_id
            HAVING count(*) > 1 LIMIT 1
        ');

        if ($conflict !== []) {
            throw new \RuntimeException(
                "Rollback dihentikan: sudah ada item dengan lebih dari satu batch pada tanggal yang sama.\n".
                "Mengembalikan kunci lama akan memaksa penggabungan/penghapusan baris, dan pembagian kuantitas per batch TIDAK BISA dipulihkan.\n".
                'Gabungkan baris-baris tersebut secara manual lebih dulu bila rollback memang dikehendaki.'
            );
        }

        DB::statement(
            'ALTER TABLE stock_entries ADD CONSTRAINT '.self::OLD_KEY.
            ' UNIQUE (tanggal, distributor_item_id)'
        );
    }

    private function guardAgainstExistingDuplicates(): void
    {
        $dups = DB::select(<<<'SQL'
            SELECT tanggal, distributor_item_id, COALESCE(batch_no, '') AS batch, count(*) AS n
            FROM stock_entries
            GROUP BY tanggal, distributor_item_id, COALESCE(batch_no, '')
            HAVING count(*) > 1
        SQL);

        if ($dups === []) {
            return;
        }

        $preview = collect($dups)->take(10)
            ->map(fn ($d) => "  - {$d->tanggal} item#{$d->distributor_item_id} batch \"{$d->batch}\" ({$d->n} baris)")
            ->implode("\n");

        throw new \RuntimeException(
            'Migration dihentikan: ditemukan '.count($dups)." kombinasi (tanggal, item, batch) yang duplikat.\n".
            $preview."\n".
            'Gabungkan baris tersebut lebih dulu, lalu jalankan ulang migration.'
        );
    }
};
