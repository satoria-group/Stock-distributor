<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Grup usaha distributor — pengelompokan resmi yang dipakai dashboard.
 *
 * Sebelumnya dashboard menebak grup dari awalan kode distributor lewat daftar
 * yang ditulis tetap di dalam kode (KFTD, SDL, UDC, GMP, MAM). Akibatnya 202
 * dari 289 distributor jatuh ke keranjang 'OTHER' — termasuk grup besar seperti
 * RNI (44 cabang), PPI (31), dan TSJ (28) yang sama sekali tidak terlihat.
 *
 * Sejak tabel ini ada, pengelompokan DIDEFINISIKAN, bukan ditebak, dan
 * menambah grup tidak lagi berarti mengubah kode.
 *
 * Bentuk berkas Excel menempel di sini, bukan di tiap cabang: seluruh cabang
 * satu grup usaha mengirim berkas dengan susunan kolom yang sama, jadi satu
 * kali setelan berlaku untuk puluhan cabang sekaligus.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('distributor_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique()->comment('Nama grup usaha, mis. UDC, KFTD, RNI');
            $table->boolean('is_active')->default(true);

            $table->string('color', 9)->nullable()
                ->comment('Warna tetap grup ini pada seluruh chart dashboard');

            $table->unsignedSmallInteger('sort_order')->default(0)
                ->comment('Urutan tampil pada filter dan chart');

            $table->foreignId('template_group_id')->nullable()
                ->constrained('distributor_template_groups')->nullOnDelete()
                ->comment('Bentuk berkas Excel yang dipakai seluruh cabang grup ini');

            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('distributors', function (Blueprint $table) {
            $table->foreignId('distributor_group_id')->nullable()->after('template_group_id')
                ->constrained('distributor_groups')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('distributors', function (Blueprint $table) {
            $table->dropConstrainedForeignId('distributor_group_id');
        });

        Schema::dropIfExists('distributor_groups');
    }
};
