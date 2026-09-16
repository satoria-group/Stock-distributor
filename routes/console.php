<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('stock:seed-dummy', function () {
    $this->info('Memulai seeding dummy stock September 2026 dari berkas Excel...');
    $seeder = new \Database\Seeders\DummyStockSeptemberSeeder();
    $seeder->run();
    $count = \App\Models\StockEntry::whereBetween('tanggal', ['2026-09-01', '2026-09-15'])->count();
    $this->info("Berhasil! Total {$count} data snapshot stock 01-15 September 2026 telah aktif di database.");
})->purpose('Seed dummy stock daily data from Excel files for September 2026');

// Otomasi pembacaan email laporan stok harian distributor setiap 1 menit
\Illuminate\Support\Facades\Schedule::command('stock:process-emails --limit=10')
    ->everyMinute()
    ->withoutOverlapping(10)
    ->runInBackground();


