<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\EmailAttachmentController;
use App\Livewire\Dashboard;
use App\Livewire\DistributorItems\Index as DistributorItemsIndex;
use App\Livewire\Distributors\Index as DistributorsIndex;
use App\Livewire\Emails\Index as EmailsIndex;
use App\Livewire\NetsuiteItems\Index as NetsuiteItemsIndex;
use App\Livewire\Stock\History as StockHistory;
use App\Livewire\Stock\Upload as StockUpload;
use App\Livewire\Users\Index as UsersIndex;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');

    Route::get('/dashboard', Dashboard::class)->name('dashboard');

    Route::get('/distributors', DistributorsIndex::class)->name('distributors.index');
    Route::get('/netsuite-items', NetsuiteItemsIndex::class)->name('netsuite-items.index');
    Route::get('/distributor-items', DistributorItemsIndex::class)->name('distributor-items.index');
    Route::get('/emails', EmailsIndex::class)->name('emails.index');
    Route::get('/emails/{uid}/attachments/{attachmentId}', [EmailAttachmentController::class, 'download'])
        ->name('emails.attachments.download');
    Route::get('/stock/upload', StockUpload::class)->name('stock.upload');
    Route::get('/stock/history', StockHistory::class)->name('stock.history');
    Route::get('/users', UsersIndex::class)->name('users.index');
});
