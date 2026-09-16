<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dpl_price_product', function (Blueprint $table) {
            $table->string('id_product', 100);
            $table->integer('id_price_region')->default(1);
            $table->decimal('price', 15, 4)->default(0);
            $table->decimal('price_reguler', 15, 4)->default(0);
            $table->decimal('dump_update_harga', 15, 4)->nullable();
            $table->string('netsuite_id', 100)->nullable();
            $table->foreignId('netsuite_item_id')->nullable()->constrained('netsuite_items')->nullOnDelete();
            $table->timestamps();

            $table->primary(['id_product', 'id_price_region']);
            $table->index('netsuite_item_id');
            $table->index('id_price_region');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dpl_price_product');
    }
};
