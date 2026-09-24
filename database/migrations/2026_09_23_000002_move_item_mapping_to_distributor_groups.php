<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pemetaan item naik dari tiap cabang ke grup usahanya.
 *
 * Satu berkas memuat seluruh cabang satu grup, dan nama itemnya sama persis di
 * semua cabang. Akibat pemetaan disimpan per cabang, 805 baris pemetaan
 * ternyata hanya mewakili 108 kombinasi (grup, nama item) yang benar-benar
 * berbeda — 87% murni pengulangan. 'RINGER LACTATE 500 ML (SATORIA)' saja
 * tersimpan 42 kali dengan hasil yang sama.
 *
 * Setelah ini satu baris berlaku untuk seluruh cabang grup, dan cabang baru
 * ikut tanpa perlu baris sendiri. Pemetaan per cabang TIDAK dihapus dari
 * rancangan: barisnya tetap sah dan berperan sebagai pengecualian, untuk item
 * yang memang hanya ada di satu cabang.
 *
 * Sekaligus: whitelist email pengirim ikut naik ke grup, karena satu berkas
 * berisi banyak cabang selalu datang dari satu alamat.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('distributor_groups', function (Blueprint $table) {
            $table->string('sender_email', 500)->nullable()->after('notes')
                ->comment('Whitelist email pengirim untuk seluruh cabang grup ini');
        });

        Schema::table('distributor_items', function (Blueprint $table) {
            $table->foreignId('distributor_group_id')->nullable()->after('distributor_id')
                ->constrained('distributor_groups')->cascadeOnDelete();
        });

        // distributor_id kosong = pemetaan milik grup, berlaku untuk semua cabang.
        DB::statement('ALTER TABLE distributor_items ALTER COLUMN distributor_id DROP NOT NULL');

        DB::statement('DROP INDEX IF EXISTS distributor_items_distributor_id_item_name_ci_unique');

        // Dua indeks unik terpisah: satu menjaga pemetaan grup, satu menjaga
        // pemetaan cabang. NULL tidak pernah bentrok di PostgreSQL, jadi
        // keduanya bisa hidup berdampingan di satu tabel.
        DB::statement('CREATE UNIQUE INDEX distributor_items_group_item_name_ci_unique
            ON distributor_items (distributor_group_id, lower(TRIM(BOTH FROM item_name)))
            WHERE distributor_group_id IS NOT NULL AND deleted_at IS NULL');

        DB::statement('CREATE UNIQUE INDEX distributor_items_distributor_item_name_ci_unique
            ON distributor_items (distributor_id, lower(TRIM(BOTH FROM item_name)))
            WHERE distributor_id IS NOT NULL AND deleted_at IS NULL');

        $this->widenSnapshotIdentity();
        $this->collapseDuplicates();
        $this->liftWhitelistToGroups();
    }

    /**
     * Identitas baris snapshot harus menyebut CABANG-nya.
     *
     * Selama pemetaan dimiliki satu cabang, kunci (tanggal, item, batch) sudah
     * cukup: item-nya sendiri yang menyiratkan cabang mana. Begitu pemetaan
     * menjadi milik grup, siratan itu hilang — dua cabang yang melaporkan item
     * dan batch sama pada hari yang sama akan saling bertabrakan, dan yang satu
     * menimpa yang lain tanpa suara.
     */
    private function widenSnapshotIdentity(): void
    {
        DB::statement('DROP INDEX IF EXISTS stock_entries_snapshot_batch_key');
        DB::statement("CREATE UNIQUE INDEX stock_entries_snapshot_batch_key
            ON stock_entries (tanggal, distributor_id, distributor_item_id, COALESCE(batch_no, ''::character varying))");
    }

    /**
     * Lebur baris kembar menjadi satu baris milik grup.
     *
     * Baris yang SUDAH ter-mapping selalu dipilih sebagai yang bertahan; kalau
     * ada beberapa, yang terlama yang menang supaya hasilnya tidak bergantung
     * pada urutan pembacaan. Snapshot stok diarahkan ulang ke baris itu sebelum
     * kembarannya dibuang, jadi tidak ada entri yang menggantung.
     */
    private function collapseDuplicates(): void
    {
        $rows = DB::table('distributor_items as di')
            ->join('distributors as d', 'd.id', '=', 'di.distributor_id')
            ->whereNotNull('d.distributor_group_id')
            ->whereNull('di.deleted_at')
            ->select([
                'di.id',
                'di.item_name',
                'di.netsuite_item_id',
                'd.distributor_group_id',
            ])
            ->orderBy('di.id')
            ->get();

        $byKey = [];
        foreach ($rows as $row) {
            $name = mb_strtolower(trim(preg_replace('/\s+/', ' ', $row->item_name)));
            $byKey[$row->distributor_group_id.'|'.$name][] = $row;
        }

        foreach ($byKey as $group) {
            $mapped = array_values(array_filter($group, fn ($r) => $r->netsuite_item_id !== null));
            $canonical = $mapped[0] ?? $group[0];

            DB::table('distributor_items')
                ->where('id', $canonical->id)
                ->update([
                    'distributor_group_id' => $canonical->distributor_group_id,
                    'distributor_id' => null,
                ]);

            $duplicateIds = array_values(array_diff(array_column($group, 'id'), [$canonical->id]));

            if ($duplicateIds === []) {
                continue;
            }

            // Entri stok tetap menyimpan distributor_id-nya sendiri, jadi
            // mengarahkannya ke baris pemetaan grup tidak menghilangkan
            // informasi cabang mana pun.
            DB::table('stock_entries')
                ->whereIn('distributor_item_id', $duplicateIds)
                ->update(['distributor_item_id' => $canonical->id]);

            DB::table('distributor_items')->whereIn('id', $duplicateIds)->delete();
        }
    }

    /**
     * Naikkan whitelist email dari cabang ke grupnya.
     *
     * Hanya dilakukan bila SELURUH cabang yang mengisinya memakai alamat yang
     * sama — kalau berbeda-beda, nilainya dibiarkan di cabang masing-masing
     * sebagai pengecualian, karena menebak mana yang "benar" justru berbahaya
     * untuk sebuah daftar izin.
     */
    private function liftWhitelistToGroups(): void
    {
        $groups = DB::table('distributors')
            ->whereNotNull('distributor_group_id')
            ->whereNotNull('sender_email')
            ->where('sender_email', '!=', '')
            ->select('distributor_group_id', 'sender_email')
            ->get()
            ->groupBy('distributor_group_id');

        foreach ($groups as $groupId => $rows) {
            $emails = $rows->pluck('sender_email')->map(fn ($e) => mb_strtolower(trim($e)))->unique();

            if ($emails->count() !== 1) {
                continue;
            }

            DB::table('distributor_groups')
                ->where('id', $groupId)
                ->update(['sender_email' => $rows->first()->sender_email]);

            DB::table('distributors')
                ->where('distributor_group_id', $groupId)
                ->update(['sender_email' => null]);
        }
    }

    public function down(): void
    {
        DB::statement("DROP INDEX IF EXISTS stock_entries_snapshot_batch_key");
        DB::statement("CREATE UNIQUE INDEX stock_entries_snapshot_batch_key
            ON stock_entries (tanggal, distributor_item_id, COALESCE(batch_no, ''::character varying))");

        DB::statement('DROP INDEX IF EXISTS distributor_items_group_item_name_ci_unique');
        DB::statement('DROP INDEX IF EXISTS distributor_items_distributor_item_name_ci_unique');

        // Baris milik grup tidak bisa dikembalikan menjadi baris per cabang
        // tanpa menebak; yang bisa dipulihkan hanyalah bentuk skemanya.
        DB::table('distributor_items')->whereNull('distributor_id')->delete();

        Schema::table('distributor_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('distributor_group_id');
        });

        DB::statement('ALTER TABLE distributor_items ALTER COLUMN distributor_id SET NOT NULL');
        DB::statement('CREATE UNIQUE INDEX distributor_items_distributor_id_item_name_ci_unique
            ON distributor_items (distributor_id, lower(TRIM(BOTH FROM item_name)))');

        Schema::table('distributor_groups', function (Blueprint $table) {
            $table->dropColumn('sender_email');
        });
    }
};
