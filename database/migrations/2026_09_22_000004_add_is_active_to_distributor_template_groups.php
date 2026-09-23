<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Status aktif untuk Grup Template.
 *
 * Sebuah grup yang resepnya sedang disusun atau sudah tidak dipakai lagi tetap
 * ikut dicobakan pada setiap berkas yang masuk — dan grup setengah jadi bisa
 * saja "berhasil" membaca berkas milik grup lain. Menonaktifkannya menyingkirkan
 * grup itu dari percobaan tanpa menghapus hasil kerja yang sudah dikumpulkan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('distributor_template_groups', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('name')
                ->comment('Grup nonaktif tidak ikut dicobakan saat membaca berkas');
        });
    }

    public function down(): void
    {
        Schema::table('distributor_template_groups', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
