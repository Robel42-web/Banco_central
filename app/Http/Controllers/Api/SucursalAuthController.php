<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class SucursalAuthController extends Controller
{
    /**
     * Inicia sesión con las credenciales de Supabase Auth
     * del proyecto independiente de Sucursales.
     */
    public function login(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $url = rtrim(
            (string) config('services.sucursales_supabase.url'),
            '/'
        );

        $key = (string) config('services.sucursales_supabase.key');

        if ($url === '' || $key === '') {
            return response()->json([
                'autenticado' => false,
                'mensaje' => 'La autenticación de Sucursales no está configurada.',
            ], 500);
        }

        // Validar las credenciales contra Supabase Auth de Sucursales.
        try {
            $respuesta = Http::acceptJson()
                ->withHeaders([
                    'apikey' => $key,
                ])
                ->timeout(10)
                ->post(
                    $url . '/auth/v1/token?grant_type=password',
                    [
                        'email' => $datos['email'],
                        'password' => $datos['password'],
                    ]
                );
        } catch (ConnectionException $e) {
            report($e);

            return response()->json([
                'autenticado' => false,
                'mensaje' => 'No fue posible conectar con la autenticación de Sucursales.',
            ], 503);
        }

        if (!$respuesta->successful()) {
            return response()->json([
                'autenticado' => false,
                'mensaje' => 'Correo o contraseña incorrectos.',
            ], 401);
        }

        $usuarioAuth = $respuesta->json('user');
        $authUserId = $usuarioAuth['id'] ?? null;

        if (!$authUserId) {
            return response()->json([
                'autenticado' => false,
                'mensaje' => 'No fue posible identificar al usuario autenticado.',
            ], 401);
        }

        // Comprobar que el usuario esté vinculado a Banco Central.
        $usuario = DB::table('branch_login_users')
            ->where('auth_user_id', $authUserId)
            ->where('status', 'active')
            ->first();

        if (!$usuario) {
            return response()->json([
                'autenticado' => false,
                'mensaje' => 'El usuario no está autorizado para acceder a una sucursal.',
            ], 403);
        }

        // Obtener la sucursal vinculada al usuario.
        $sucursal = DB::table('branches')
            ->where('id', $usuario->branch_id)
            ->where('status', 'active')
            ->first();

        if (!$sucursal) {
            return response()->json([
                'autenticado' => false,
                'mensaje' => 'La sucursal no existe o no está activa.',
            ], 403);
        }

        // Buscar una API Key activa, no expirada y almacenada cifrada.
        $registroKey = DB::table('node_api_keys')
            ->where('node_type', 'branch')
            ->where('branch_id', $sucursal->id)
            ->where('status', 'active')
            ->whereNotNull('key_encrypted')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->orderByDesc('created_at')
            ->first();

        if (!$registroKey) {
            return response()->json([
                'autenticado' => false,
                'mensaje' => 'La sucursal no tiene una API Key recuperable activa. Debe generarse una nueva.',
            ], 503);
        }

        // Descifrar la API Key usando APP_KEY.
        try {
            $apiKey = Crypt::decryptString(
                $registroKey->key_encrypted
            );
        } catch (DecryptException $e) {
            report($e);

            return response()->json([
                'autenticado' => false,
                'mensaje' => 'No fue posible recuperar la API Key.',
            ], 500);
        }

        return response()->json([
            'autenticado' => true,
            'mensaje' => 'Inicio de sesión correcto.',
            'usuario' => [
                'id' => $usuario->auth_user_id,
                'nombre' => $usuario->full_name,
                'email' => $usuario->email,
                'rol' => $usuario->role,
            ],
            'sucursal' => [
                'id' => $sucursal->id,
                'codigo' => $sucursal->branch_code,
                'nombre' => $sucursal->name,
                'direccion' => $sucursal->address,
                'responsable' => $sucursal->responsible_name,
                'estado' => $sucursal->status,
            ],
            'api_key' => $apiKey,
        ]);
    }

    /**
     * Validar el acceso de una sucursal que ya tiene API Key.
     * Esta ruta sigue protegida por AuthenticateNodeApiKey.
     */
    public function validarAcceso(Request $request): JsonResponse
    {
        $nodo = $request->attributes->get('node_api_key');

        if (!$nodo || $nodo->node_type !== 'branch') {
            return response()->json([
                'valida' => false,
                'mensaje' => 'El acceso es exclusivo para sucursales.',
            ], 403);
        }

        $sucursal = DB::table('branches')
            ->where('id', $nodo->branch_id)
            ->first();

        if (!$sucursal || $sucursal->status !== 'active') {
            return response()->json([
                'valida' => false,
                'mensaje' => 'La sucursal no existe o no está activa.',
            ], 403);
        }

        return response()->json([
            'valida' => true,
            'mensaje' => 'Acceso autorizado.',
            'sucursal' => [
                'id' => $sucursal->id,
                'codigo' => $sucursal->branch_code,
                'nombre' => $sucursal->name,
                'estado' => $sucursal->status,
            ],
        ]);
    }
}
