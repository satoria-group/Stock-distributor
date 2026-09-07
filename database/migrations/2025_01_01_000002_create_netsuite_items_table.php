<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('netsuite_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('internal_id')->nullable()->comment('Internal ID dari Netsuite');
            $table->string('netsuite_id')->unique()->comment('Netsuite Item Id, mis. 14IVD10GR500');
            $table->string('netsuite_name');
            $table->string('default_satuan')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('netsuite_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('netsuite_items');
    }
};
