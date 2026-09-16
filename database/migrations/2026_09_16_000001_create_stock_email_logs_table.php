<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_email_logs', function (Blueprint $table) {
            $table->id();
            $table->string('email_uid')->index();
            $table->string('message_id')->nullable()->index();
            $table->string('from_email');
            $table->string('from_name')->nullable();
            $table->string('subject');
            $table->foreignId('distributor_id')->nullable()->constrained('distributors')->nullOnDelete();
            $table->string('distributor_code')->nullable();
            $table->date('tanggal_snapshot')->nullable();
            $table->string('filename')->nullable();
            $table->unsignedInteger('file_size_bytes')->nullable();
            $table->string('status', 30)->default('pending')->index(); // success, partial_unmapped, invalid_template, unauthorized_sender, failed, skipped
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('imported_rows')->default(0);
            $table->unsignedInteger('skipped_rows')->default(0);
            $table->text('error_message')->nullable();
            $table->json('details')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_email_logs');
    }
};
