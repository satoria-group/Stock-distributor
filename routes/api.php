<?php

use App\Http\Controllers\Api\StockPushController;
use App\Http\Middleware\AuthenticateApiClient;
use Illuminate\Support\Facades\Route;

// API push stok untuk distributor yang punya sistem sendiri (saat ini: SDL).
// Distributor lain tetap memakai upload Excel / email.
Route::prefix('v1')
    ->middleware([AuthenticateApiClient::class, 'throttle:stock-api'])
    ->group(function () {
        Route::post('/stock', [StockPushController::class, 'store'])->name('api.stock.store');
        Route::get('/stock/{requestId}', [StockPushController::class, 'show'])->name('api.stock.show');
    });
