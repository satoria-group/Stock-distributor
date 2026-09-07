<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('distributor_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distributor_id')->constrained('distributors')->cascadeOnDelete();
            $table->string('source_item_id')->nullable()->comment('Item Id versi distributor, kalau ada');
            $table->string('item_name')->comment('Nama item versi distributor, dari sheet Distributor Item');
            $table->string('satuan')->nullable();
            $table->foreignId('netsuite_item_id')->nullable()->constrained('netsuite_items')->nullOnDelete();
            $table->string('netsuite_satuan')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['distributor_id', 'item_name']);
            $table->index('netsuite_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('distributor_items');
    }
};
