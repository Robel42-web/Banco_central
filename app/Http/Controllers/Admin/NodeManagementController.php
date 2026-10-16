<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Throwable;

class NodeManagementController extends Controller
{
    public function createBranch()
    {
        return view('admin.nodes.branch-create');
    }

    public function createAtm()
    {
        $branches = DB::table('branches')
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'branch_code', 'name']);

        return view('admin.nodes.atm-create', compact('branches'));
    }

    public function storeBranch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'branch_code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('branches', 'branch_code'),
            ],
            'name' => ['required', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:2000'],
            'responsible_name' => ['nullable', 'string', 'max:150'],
            'cash_available' => [
                'required',
                'numeric',
                'min:0',
                'max:999999999999.99',
            ],
            'admin_email' => [
                'required',
                'email',
                'max:254',
            ],
            'admin_password' => [
                'required',
                'string',
                'min:12',
                'confirmed',
            ],
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput($request->except([
                    'admin_password',
                    'admin_password_confirmation',
                ]));
        }

        $data = $validator->validated();

        $url = rtrim(
            (string) config('services.sucursales_supabase.url'),
            '/'
        );

        $publicKey = (string) config(
            'services.sucursales_supabase.key'
        );

        $serviceKey = (string) config(
            'services.sucursales_supabase.service_key'
        );

        if ($url === '' || $publicKey === '' || $serviceKey === '') {
            return back()
                ->withErrors([
                    'admin_email' =>
                        'Falta configurar la conexión con Supabase de Sucursales.',
                ])
                ->withInput($request->except([
                    'admin_password',
                    'admin_password_confirmation',
                ]));
        }

        $branchId = (string) Str::uuid();
        $apiKey = 'branch_' . bin2hex(random_bytes(32));

        /*
         * Crear el usuario administrador en Supabase Auth.
         * La contraseña se transmite por HTTPS y no se guarda
         * en la base de datos central.
         *
         * La clave secreta se envía mediante apikey.
         * No se utiliza withToken(), ya que sb_secret_ no es un JWT.
         */
        try {
            $respuesta = Http::acceptJson()
                ->withHeaders([
                    'apikey' => $serviceKey,
                ])
                ->timeout(15)
                ->post($url . '/auth/v1/admin/users', [
                    'email' => $data['admin_email'],
                    'password' => $data['admin_password'],
                    'email_confirm' => true,
                    'user_metadata' => [
                        'full_name' => $data['responsible_name']
                            ?: $data['name'],
                        'branch_code' => $data['branch_code'],
                        'role' => 'branch_admin',
                    ],
                ]);
        } catch (ConnectionException $e) {
            report($e);

            return back()
                ->withErrors([
                    'admin_email' =>
                        'No fue posible conectar con Supabase de Sucursales.',
                ])
                ->withInput($request->except([
                    'admin_password',
                    'admin_password_confirmation',
                ]));
        }

        if (!$respuesta->successful()) {
            $mensaje = $respuesta->json('msg')
                ?? $respuesta->json('message')
                ?? 'Supabase rechazó la creación del usuario. Verifica las credenciales administrativas.';

            return back()
                ->withErrors([
                    'admin_email' => $mensaje,
                ])
                ->withInput($request->except([
                    'admin_password',
                    'admin_password_confirmation',
                ]));
        }

        $authUserId = $respuesta->json('id');

        if (!$authUserId) {
            return back()
                ->withErrors([
                    'admin_email' =>
                        'Supabase no devolvió el identificador del usuario. Verifica Auth antes de volver a intentarlo.',
                ])
                ->withInput($request->except([
                    'admin_password',
                    'admin_password_confirmation',
                ]));
        }

        /*
         * Registrar sucursal, API Key y vínculo con el usuario
         * de Supabase en una única transacción local.
         */
        try {
            DB::transaction(function () use (
                $data,
                $branchId,
                $apiKey,
                $authUserId
            ) {
                DB::table('branches')->insert([
                    'id' => $branchId,
                    'branch_code' => $data['branch_code'],
                    'name' => $data['name'],
                    'address' => $data['address'] ?? null,
                    'responsible_name' =>
                        $data['responsible_name'] ?? null,
                    'cash_available' => $data['cash_available'],
                    'status' => 'active',
                ]);

                $this->saveApiKey(
                    $apiKey,
                    'branch',
                    $branchId,
                    null,
                    'API Key para sucursal '
                        . $data['branch_code']
                );

                DB::table('branch_login_users')->insert([
                    'id' => (string) Str::uuid(),
                    'auth_user_id' => $authUserId,
                    'branch_id' => $branchId,
                    'email' => $data['admin_email'],
                    'full_name' => $data['responsible_name']
                        ?: $data['name'],
                    'role' => 'branch_admin',
                    'status' => 'active',
                ]);
            });
        } catch (Throwable $e) {
            report($e);

            /*
             * Si falla la base central, intentar eliminar
             * el usuario remoto recién creado.
             */
            try {
                $eliminacion = Http::acceptJson()
                    ->withHeaders([
                        'apikey' => $serviceKey,
                    ])
                    ->timeout(10)
                    ->delete(
                        $url . '/auth/v1/admin/users/' . $authUserId
                    );

                if (!$eliminacion->successful()) {
                    report(new \RuntimeException(
                        'No se pudo eliminar el usuario remoto '
                        . 'de Supabase tras fallar el registro central. '
                        . 'Auth user ID: '
                        . $authUserId
                    ));
                }
            } catch (Throwable $errorEliminacion) {
                report($errorEliminacion);
            }

            return back()
                ->withErrors([
                    'admin_email' =>
                        'No se pudo completar el registro central. Comprueba los registros del servidor antes de volver a intentarlo.',
                ])
                ->withInput($request->except([
                    'admin_password',
                    'admin_password_confirmation',
                ]));
        }

        return view('admin.nodes.key-created', [
            'apiKey' => $apiKey,
            'nodeName' => $data['name'],
            'nodeCode' => $data['branch_code'],
            'nodeType' => 'Sucursal',
            'returnRoute' => 'admin.branches',
        ]);
    }

    public function storeAtm(Request $request)
    {
        $data = $request->validate([
            'atm_code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('atms', 'atm_code'),
            ],
            'branch_id' => [
                'nullable',
                'uuid',
                Rule::exists('branches', 'id')
                    ->where('status', 'active'),
            ],
            'location' => ['nullable', 'string', 'max:2000'],
            'responsible_name' => [
                'nullable',
                'string',
                'max:150',
            ],
            'reported_cash_available' => [
                'required',
                'numeric',
                'min:0',
                'max:999999999999.99',
            ],
        ]);

        $apiKey = 'atm_' . bin2hex(random_bytes(32));
        $atmId = (string) Str::uuid();

        DB::transaction(function () use ($data, $apiKey, $atmId) {
            DB::table('atms')->insert([
                'id' => $atmId,
                'atm_code' => $data['atm_code'],
                'branch_id' => $data['branch_id'] ?? null,
                'location' => $data['location'] ?? null,
                'responsible_name' =>
                    $data['responsible_name'] ?? null,
                'reported_cash_available' =>
                    $data['reported_cash_available'],
                'status' => 'active',
            ]);

            $this->saveApiKey(
                $apiKey,
                'atm',
                null,
                $atmId,
                'API Key para cajero ' . $data['atm_code']
            );
        });

        return view('admin.nodes.key-created', [
            'apiKey' => $apiKey,
            'nodeName' => $data['location']
                ?: $data['atm_code'],
            'nodeCode' => $data['atm_code'],
            'nodeType' => 'Cajero ATM',
            'returnRoute' => 'admin.atms',
        ]);
    }

    private function saveApiKey(
        string $apiKey,
        string $nodeType,
        ?string $branchId,
        ?string $atmId,
        string $description
    ): void {
        DB::table('node_api_keys')->insert([
            'id' => (string) Str::uuid(),
            'node_type' => $nodeType,
            'branch_id' => $branchId,
            'atm_id' => $atmId,
            'key_hash' => hash('sha256', $apiKey),
            'key_encrypted' => Crypt::encryptString($apiKey),
            'key_prefix' => substr($apiKey, 0, 20),
            'description' => $description,
            'status' => 'active',
        ]);
    }

    public function revokeApiKey(string $id)
    {
        $updated = DB::table('node_api_keys')
            ->where('id', $id)
            ->where('status', 'active')
            ->update(['status' => 'revoked']);

        if (!$updated) {
            return back()->withErrors([
                'api_key' =>
                    'La clave no existe, ya fue revocada o no pudo modificarse.',
            ]);
        }

        return back()->with(
            'success',
            'La API Key fue revocada correctamente.'
        );
    }

    public function deactivateBranch(string $id)
    {
        $branch = DB::table('branches')
            ->where('id', $id)
            ->first();

        if (!$branch) {
            return back()->withErrors([
                'node' => 'La sucursal no existe.',
            ]);
        }

        if ($branch->status === 'inactive') {
            return back()->with(
                'success',
                'La sucursal ya está inactiva.'
            );
        }

        DB::transaction(function () use ($id) {
            DB::table('branches')
                ->where('id', $id)
                ->update(['status' => 'inactive']);

            DB::table('node_api_keys')
                ->where('branch_id', $id)
                ->where('node_type', 'branch')
                ->where('status', 'active')
                ->update(['status' => 'revoked']);
        });

        return back()->with(
            'success',
            'Sucursal desactivada y sus API Keys activas revocadas.'
        );
    }

    public function deactivateAtm(string $id)
    {
        $atm = DB::table('atms')
            ->where('id', $id)
            ->first();

        if (!$atm) {
            return back()->withErrors([
                'node' => 'El cajero no existe.',
            ]);
        }

        if ($atm->status === 'inactive') {
            return back()->with(
                'success',
                'El cajero ya está inactivo.'
            );
        }

        DB::transaction(function () use ($id) {
            DB::table('atms')
                ->where('id', $id)
                ->update(['status' => 'inactive']);

            DB::table('node_api_keys')
                ->where('atm_id', $id)
                ->where('node_type', 'atm')
                ->where('status', 'active')
                ->update(['status' => 'revoked']);
        });

        return back()->with(
            'success',
            'Cajero desactivado y sus API Keys activas revocadas.'
        );
    }
}
