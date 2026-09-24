<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dua setelan yang dituntut bentuk berkas yang sesungguhnya beredar.
 *
 * fill_down — berkas PPI berupa pivot table: kolom cabang dan nama produk
 * hanya terisi di baris pertama tiap blok, sisanya kosong dan berarti "sama
 * seperti di atas". Dibaca apa adanya, 90% barisnya kehilangan identitas.
 *
 * sheet_name kini boleh berupa POLA (mis. 'SDL *') — berkas SDL memuat satu
 * sheet per cabang, dan cabang baru tidak boleh menuntut perubahan setelan.
 * Kolomnya sendiri tidak berubah bentuk, hanya maknanya yang diperluas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('distributor_template_groups', function (Blueprint $table) {
            $table->json('fill_down')->nullable()->after('column_map')
                ->comment('Kolom kanonik yang nilainya diwarisi dari baris di atasnya bila kosong');
        });
    }

    public function down(): void
    {
        Schema::table('distributor_template_groups', function (Blueprint $table) {
            $table->dropColumn('fill_down');
        });
    }
};
