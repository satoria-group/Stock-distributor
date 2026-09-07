<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * stock_entries is a SNAPSHOT table: `quantity` is the final stock figure
 * for that (tanggal, distributor_item) on that date — not an in/out
 * movement. Re-uploading the same date+distributor+item replaces the row
 * (see the unique key below, used as the upsert key from the upload grid).
 *
 * Confirmed with user 2026-09-04:
 *  1. Snapshot, not mutation.
 *  2. batch_no & expired_date are optional.
 *  3. Rows whose distributor_item has no netsuite_item_id yet are still
 *     saved (status "belum ter-mapping"), never rejected at upload time.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_entries', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->foreignId('distributor_id')->constrained('distributors')->cascadeOnDelete();
            $table->foreignId('distributor_item_id')->constrained('distributor_items')->cascadeOnDelete();
            $table->decimal('quantity', 14, 2)->default(0);
            $table->string('satuan')->nullable();
            $table->date('expired_date')->nullable();
            $table->string('batch_no')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['tanggal', 'distributor_item_id'], 'stock_entries_snapshot_key');
            $table->index(['distributor_id', 'tanggal']);
            $table->index('expired_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_entries');
    }
};
