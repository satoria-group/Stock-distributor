<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_snapshot_activities', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->foreignId('distributor_id')->constrained('distributors')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action'); // 'automation', 'upload', 'edit', 'merge', 'review'
            $table->string('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['distributor_id', 'tanggal'], 'idx_snapshot_activity');
            $table->index('tanggal');
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_snapshot_activities');
    }
};
