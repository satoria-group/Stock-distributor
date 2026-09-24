<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Satuan NetSuite dibaca langsung dari netsuite_items.default_satuan lewat
// relasi, supaya perubahan di master NetSuite langsung berlaku di semua mapping.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('distributor_items', function (Blueprint $table) {
            $table->dropColumn('netsuite_satuan');
        });
    }

    public function down(): void
    {
        Schema::table('distributor_items', function (Blueprint $table) {
            $table->string('netsuite_satuan')->nullable()->after('netsuite_item_id');
        });
    }
};
