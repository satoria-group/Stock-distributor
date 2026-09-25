<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Akses API push stok untuk distributor yang punya sistem sendiri.
 *
 * Token menempel pada GRUP USAHA: satu perusahaan mengirim data untuk seluruh
 * cabangnya dari satu sistem, dan hanya boleh mengirim untuk cabangnya sendiri.
 * Yang disimpan hanya hash SHA-256 token; token asli ditampilkan sekali saja.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('distributor_group_id')->constrained('distributor_groups')->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            // Dipisah koma; kosong = semua IP boleh.
            $table->text('allowed_ips')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_api_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_client_id')->nullable()->constrained('api_clients')->nullOnDelete();
            // Unik per klien: kiriman yang diulang tidak diproses dua kali.
            $table->string('request_id', 100);
            $table->string('ip_address', 45)->nullable();
            $table->date('tanggal_snapshot')->nullable();
            $table->string('status', 30)->index();
            $table->unsignedInteger('branch_count')->default(0);
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('imported_rows')->default(0);
            $table->unsignedInteger('skipped_rows')->default(0);
            $table->text('error_message')->nullable();
            $table->json('details')->nullable();
            $table->timestamps();

            $table->unique(['api_client_id', 'request_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_api_logs');
        Schema::dropIfExists('api_clients');
    }
};
