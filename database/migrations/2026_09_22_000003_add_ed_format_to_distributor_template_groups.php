<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Format tanggal kedaluwarsa (ED) per grup.
 *
 * ED sebelumnya selalu ditebak otomatis. Itu cukup selama bentuknya tanggal
 * penuh, tapi ada grup yang menulisnya sebagai bulan/tahun saja (mis. 12/2027)
 * — bentuk yang mustahil ditebak dengan benar: '12/2027' tidak punya hari, dan
 * '01/02/2027' pun ambigu antara 1 Februari dan 2 Januari.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('distributor_template_groups', function (Blueprint $table) {
            $table->string('ed_format')->nullable()->after('date_format')
                ->comment('Format kolom ED; NULL = deteksi otomatis. Format bulan/tahun dipakai tanggal 1.');
        });
    }

    public function down(): void
    {
        Schema::table('distributor_template_groups', function (Blueprint $table) {
            $table->dropColumn('ed_format');
        });
    }
};
