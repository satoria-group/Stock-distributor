<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_conversions', function (Blueprint $table) {
            $table->id();
            // Disimpan huruf besar; pencocokan tidak peka besar-kecil huruf.
            $table->string('from_unit', 50)->unique();
            $table->string('to_unit', 50);
            $table->decimal('factor', 14, 4);
            $table->timestamps();
        });

        DB::table('unit_conversions')->insert([
            'from_unit' => 'BOX',
            'to_unit' => 'PCS',
            'factor' => 50,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::table('stock_entries', function (Blueprint $table) {
            // Nilai persis dari berkas; hanya terisi bila baris dikonversi.
            $table->decimal('quantity_asli', 14, 2)->nullable()->after('satuan');
            $table->string('satuan_asli', 50)->nullable()->after('quantity_asli');
        });
    }

    public function down(): void
    {
        Schema::table('stock_entries', function (Blueprint $table) {
            $table->dropColumn(['quantity_asli', 'satuan_asli']);
        });

        Schema::dropIfExists('unit_conversions');
    }
};
