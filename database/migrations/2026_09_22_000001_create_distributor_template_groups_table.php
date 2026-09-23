<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('distributor_template_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique()->comment('Nama grup template, mis. UDC (mencakup semua cabangnya)');
            $table->text('notes')->nullable();

            $table->json('column_map')->nullable()
                ->comment('Nama kolom kanonik => judul kolom ternormalisasi pada berkas grup ini');

            $table->unsignedSmallInteger('header_row')->nullable()
                ->comment('Nomor baris header (1-based). NULL = deteksi otomatis');

            $table->string('sheet_name')->nullable()
                ->comment('Nama sheet yang dibaca. NULL = sheet Template, atau sheet aktif');

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('distributors', function (Blueprint $table) {
            $table->foreignId('template_group_id')->nullable()->after('sender_email')
                ->constrained('distributor_template_groups')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('distributors', function (Blueprint $table) {
            $table->dropConstrainedForeignId('template_group_id');
        });

        Schema::dropIfExists('distributor_template_groups');
    }
};
