<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Setelan pembacaan berkas yang tidak bisa diungkapkan lewat pemetaan kolom.
 *
 * Kelima grup nyata (UDC, GMP, SDL, MAM, KFTD) masing-masing melanggar satu
 * asumsi berbeda: tanggalnya terpecah tiga kolom, periodenya cuma bulan,
 * batch-nya tidak ada sama sekali, atau baris berstok nol ikut terkirim.
 * Semuanya sifat grup, bukan sifat satu kolom — karena itu tempatnya di sini,
 * bukan di dalam column_map.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('distributor_template_groups', function (Blueprint $table) {
            $table->string('date_mode')->default('auto')->after('column_map')
                ->comment('auto | format | parts_dmy | month');

            $table->string('date_format')->nullable()->after('date_mode')
                ->comment('Format tanggal PHP bila date_mode=format/month, mis. d/m/Y atau M-Y');

            $table->string('default_batch')->nullable()->after('date_format')
                ->comment('Nomor batch pengganti untuk grup yang berkasnya tidak punya kolom batch');

            $table->boolean('skip_nonpositive_qty')->default(false)->after('default_batch')
                ->comment('Lewati baris dengan kuantitas <= 0 alih-alih mengimpornya');
        });
    }

    public function down(): void
    {
        Schema::table('distributor_template_groups', function (Blueprint $table) {
            $table->dropColumn(['date_mode', 'date_format', 'default_batch', 'skip_nonpositive_qty']);
        });
    }
};
