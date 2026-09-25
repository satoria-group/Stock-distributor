<?php

namespace App\Http\Middleware;

use App\Models\ApiClient;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Autentikasi mesin-ke-mesin untuk API push stok: header
 * `Authorization: Bearer <token>`. Klien yang lolos ditaruh di atribut
 * request `api_client`.
 */
class AuthenticateApiClient
{
    public function handle(Request $request, Closure $next): Response
    {
        $client = ApiClient::findByToken($request->bearerToken());

        if (! $client || ! $client->is_active) {
            return response()->json([
                'status' => 'unauthorized',
                'message' => 'Token API tidak valid atau sudah dicabut.',
            ], 401);
        }

        if (! $client->allowsIp($request->ip())) {
            return response()->json([
                'status' => 'forbidden_ip',
                'message' => "Alamat IP {$request->ip()} tidak diizinkan untuk token ini.",
            ], 403);
        }

        $client->forceFill(['last_used_at' => now()])->saveQuietly();
        $request->attributes->set('api_client', $client);

        return $next($request);
    }
}
