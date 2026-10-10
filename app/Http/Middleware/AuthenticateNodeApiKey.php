<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateNodeApiKey
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $apiKey = $request->bearerToken()
            ?? $request->header('X-API-Key');

        if (!$apiKey || strlen($apiKey) > 512) {
            return response()->json([
                'mensaje' => 'API Key requerida o inválida',
            ], 401);
        }

        // La tabla almacena el hash SHA-256, nunca la clave original.
        $hash = hash('sha256', $apiKey);

        $nodo = DB::table('node_api_keys')
            ->where('key_hash', $hash)
            ->where('status', 'active')
            ->whereIn('node_type', ['branch', 'atm'])
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();

        if (!$nodo) {
            return response()->json([
                'mensaje' => 'API Key inválida, revocada o expirada',
            ], 401);
        }

        DB::table('node_api_keys')
            ->where('id', $nodo->id)
            ->update([
                'last_used_at' => now(),
            ]);

        // La operación podrá identificar al nodo autenticado.
        $request->attributes->set('node_api_key', $nodo);

        return $next($request);
    }
}