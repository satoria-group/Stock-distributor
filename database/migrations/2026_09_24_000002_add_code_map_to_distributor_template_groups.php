<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('distributor_template_groups', function (Blueprint $table) {
            $table->json('code_map')->nullable()->after('column_map');
        });
    }

    public function down(): void
    {
        Schema::table('distributor_template_groups', function (Blueprint $table) {
            $table->dropColumn('code_map');
        });
    }
};
