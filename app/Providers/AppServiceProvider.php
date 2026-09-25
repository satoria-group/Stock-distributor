<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // API push stok: dibatasi per klien (token), bukan per IP.
        RateLimiter::for('stock-api', function (Request $request) {
            $client = $request->attributes->get('api_client');

            return Limit::perMinute(60)->by($client ? 'client:'.$client->id : 'ip:'.$request->ip());
        });
    }
}
