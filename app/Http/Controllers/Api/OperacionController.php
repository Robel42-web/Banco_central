<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cuenta;
use App\Models\Movimiento;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OperacionController extends Controller
{
    private const MAX_CENTAVOS = 99999999999999;

    public function deposito(Request $request): JsonResponse
    {
        return $this->procesar($request, 'deposit');
    }

    public function retiro(Request $request): JsonResponse
    {
        return $this->procesar($request, 'withdrawal');
    }

    private function procesar(
        Request $request,
        string $tipo
    ): JsonResponse {
        $datos = $request->validate([
            'cuenta_id' => ['required', 'uuid'],
            'monto_centavos' => [
                'required',
                'integer',
                'min:1',
                'max:' . self::MAX_CENTAVOS,
            ],
            'idempotency_key' => [
                'required_without:referencia',
                'nullable',
                'string',
                'max:100',
            ],
            'referencia' => [
                'required_without:idempotency_key',
                'nullable',
                'string',
                'max:100',
            ],
            'descripcion' => ['nullable', 'string', 'max:255'],
        ]);

        $idempotencyKey =
            $datos['idempotency_key'] ?? $datos['referencia'];

        $monto = (int) $datos['monto_centavos'];
        $importe = $this->aDecimal($monto);

        $nodeKey = $request->attributes->get('node_api_key');

        if (
            !$nodeKey ||
            !in_array($nodeKey->node_type, ['branch', 'atm'], true)
        ) {
            return response()->json([
                'mensaje' => 'Se requiere una API Key válida de sucursal o cajero',
            ], 403);
        }

        $esSucursal = $nodeKey->node_type === 'branch';

        if (
            ($esSucursal && empty($nodeKey->branch_id)) ||
            (!$esSucursal && empty($nodeKey->atm_id))
        ) {
            return response()->json([
                'mensaje' => 'La API Key no tiene un nodo asociado válido',
            ], 403);
        }

        try {
            $resultado = DB::transaction(function () use (
                $datos,
                $tipo,
                $idempotencyKey,
                $monto,
                $importe,
                $nodeKey,
                $esSucursal
            ) {
                // Evita procesar dos veces la misma solicitud.
                $existente = Movimiento::where(
                    'idempotency_key',
                    $idempotencyKey
                )->first();

                if ($existente) {
                    if ($this->esMismaOperacion(
                        $existente,
                        $datos['cuenta_id'],
                        $monto,
                        $tipo,
                        $nodeKey
                    )) {
                        return [
                            'movimiento' => $existente,
                            'repetida' => true,
                        ];
                    }

                    return [
                        'error' => 'La clave de idempotencia ya se utilizó en otra operación',
                        'status' => 409,
                    ];
                }

                // Bloquea el efectivo del nodo durante la operación.
                if ($esSucursal) {
                    $nodo = DB::table('branches')
                        ->where('id', $nodeKey->branch_id)
                        ->lockForUpdate()
                        ->first();

                    if (!$nodo || $nodo->status !== 'active') {
                        return [
                            'error' => 'La sucursal no existe o no está activa',
                            'status' => 422,
                        ];
                    }

                    $efectivoAnterior = $this->aCentavos(
                        $nodo->cash_available
                    );

                    $branchId = $nodo->id;
                    $atmId = null;
                } else {
                    $nodo = DB::table('atms')
                        ->where('id', $nodeKey->atm_id)
                        ->lockForUpdate()
                        ->first();

                    if (!$nodo || $nodo->status !== 'active') {
                        return [
                            'error' => 'El cajero no existe o no está activo',
                            'status' => 422,
                        ];
                    }

                    // No operar con un cajero cuya sucursal esté inactiva.
                    $sucursalActiva = DB::table('branches')
                        ->where('id', $nodo->branch_id)
                        ->where('status', 'active')
                        ->exists();

                    if (!$sucursalActiva) {
                        return [
                            'error' => 'La sucursal asociada al cajero no está activa',
                            'status' => 422,
                        ];
                    }

                    $efectivoAnterior = $this->aCentavos(
                        $nodo->reported_cash_available
                    );

                    $branchId = null;
                    $atmId = $nodo->id;
                }

                // Bloquea la cuenta para evitar cambios simultáneos
                // que puedan producir saldos incorrectos.
                $cuenta = Cuenta::whereKey($datos['cuenta_id'])
                    ->lockForUpdate()
                    ->first();

                if (!$cuenta) {
                    return [
                        'error' => 'Cuenta no encontrada',
                        'status' => 404,
                    ];
                }

                if ($cuenta->status !== 'active') {
                    return [
                        'error' => 'La cuenta no está activa',
                        'status' => 422,
                    ];
                }

                $saldoAnterior = $this->aCentavos($cuenta->balance);

                if (
                    $tipo === 'withdrawal' &&
                    $saldoAnterior < $monto
                ) {
                    return [
                        'error' => 'Saldo insuficiente en la cuenta',
                        'status' => 422,
                    ];
                }

                if (
                    $tipo === 'withdrawal' &&
                    $efectivoAnterior < $monto
                ) {
                    return [
                        'error' => 'Efectivo insuficiente en el nodo',
                        'status' => 422,
                    ];
                }

                if (
                    $tipo === 'deposit' &&
                    $saldoAnterior > self::MAX_CENTAVOS - $monto
                ) {
                    return [
                        'error' => 'El saldo excedería el límite permitido',
                        'status' => 422,
                    ];
                }

                if (
                    $tipo === 'deposit' &&
                    $efectivoAnterior > self::MAX_CENTAVOS - $monto
                ) {
                    return [
                        'error' => 'El efectivo excedería el límite permitido',
                        'status' => 422,
                    ];
                }

                $saldoNuevo = $tipo === 'deposit'
                    ? $saldoAnterior + $monto
                    : $saldoAnterior - $monto;

                $efectivoNuevo = $tipo === 'deposit'
                    ? $efectivoAnterior + $monto
                    : $efectivoAnterior - $monto;

                $ahora = now();
                $transaccionId = (string) Str::uuid();

                // Actualiza el saldo de la cuenta.
                $cuenta->balance = $this->aDecimal($saldoNuevo);
                $cuenta->save();

                // Registra la transacción en el nodo correcto.
                $movimiento = Movimiento::create([
                    'id' => $transaccionId,
                    'transaction_code' =>
                        'TXN-' . strtoupper((string) Str::uuid()),
                    'account_origin_id' =>
                        $tipo === 'withdrawal' ? $cuenta->id : null,
                    'account_destination_id' =>
                        $tipo === 'deposit' ? $cuenta->id : null,
                    'amount' => $importe,
                    'type' => $tipo,
                    'status' => 'completed',
                    'branch_id' => $branchId,
                    'atm_id' => $atmId,
                    'node_api_key_id' => $nodeKey->id,
                    'idempotency_key' => $idempotencyKey,
                    'description' => $datos['descripcion'] ?? null,
                    'created_at' => $ahora,
                ]);

                // Actualiza el efectivo de la sucursal o del cajero.
                if ($esSucursal) {
                    DB::table('branches')
                        ->where('id', $nodo->id)
                        ->update([
                            'cash_available' =>
                                $this->aDecimal($efectivoNuevo),
                            'updated_at' => $ahora,
                        ]);
                } else {
                    DB::table('atms')
                        ->where('id', $nodo->id)
                        ->update([
                            'reported_cash_available' =>
                                $this->aDecimal($efectivoNuevo),
                            'last_reported_at' => $ahora,
                            'updated_at' => $ahora,
                        ]);
                }

                // Registra la entrada o salida de efectivo.
                DB::table('node_cash_movements')->insert([
                    'branch_id' => $branchId,
                    'atm_id' => $atmId,
                    'transaction_id' => $movimiento->id,
                    'movement_type' =>
                        $tipo === 'deposit' ? 'in' : 'out',
                    'amount' => $importe,
                    'description' => $datos['descripcion'] ?? (
                        $tipo === 'deposit'
                            ? 'Depósito en efectivo'
                            : 'Retiro de efectivo'
                    ),
                    'created_at' => $ahora,
                ]);

                return [
                    'movimiento' => $movimiento,
                    'repetida' => false,
                ];
            }, 3);
        } catch (QueryException $e) {
            // PostgreSQL: 23505 = violación de restricción UNIQUE.
            $sqlState = $e->errorInfo[0] ?? (string) $e->getCode();

            if ($sqlState !== '23505') {
                throw $e;
            }

            $existente = Movimiento::where(
                'idempotency_key',
                $idempotencyKey
            )->first();

            if (!$existente) {
                throw $e;
            }

            if (!$this->esMismaOperacion(
                $existente,
                $datos['cuenta_id'],
                $monto,
                $tipo,
                $nodeKey
            )) {
                return response()->json([
                    'mensaje' => 'La clave de idempotencia ya se utilizó en otra operación',
                ], 409);
            }

            $resultado = [
                'movimiento' => $existente,
                'repetida' => true,
            ];
        }

        if (isset($resultado['error'])) {
            return response()->json([
                'mensaje' => $resultado['error'],
            ], $resultado['status']);
        }

        $cuentaActual = Cuenta::findOrFail($datos['cuenta_id']);
        $saldoCentavos = $this->aCentavos($cuentaActual->balance);

        return response()->json([
            'mensaje' => $resultado['repetida']
                ? 'La operación ya había sido registrada'
                : (
                    $tipo === 'deposit'
                        ? 'Depósito registrado correctamente'
                        : 'Retiro registrado correctamente'
                ),
            'repetida' => $resultado['repetida'],
            'cuenta_id' => $cuentaActual->id,
            'saldo_centavos' => $saldoCentavos,
            'saldo' => $this->aDecimal($saldoCentavos),
            'moneda' => 'MXN',
            'transaccion' => $resultado['movimiento'],
        ], $resultado['repetida'] ? 200 : 201);
    }

    private function esMismaOperacion(
        Movimiento $movimiento,
        string $cuentaId,
        int $monto,
        string $tipo,
        $nodeKey
    ): bool {
        if ($tipo === 'withdrawal') {
            $cuentaCoincide =
                $movimiento->account_origin_id === $cuentaId &&
                $movimiento->account_destination_id === null;
        } else {
            $cuentaCoincide =
                $movimiento->account_destination_id === $cuentaId &&
                $movimiento->account_origin_id === null;
        }

        if ($nodeKey->node_type === 'branch') {
            $nodoCoincide =
                $movimiento->branch_id === $nodeKey->branch_id &&
                $movimiento->atm_id === null;
        } else {
            $nodoCoincide =
                $movimiento->atm_id === $nodeKey->atm_id &&
                $movimiento->branch_id === null;
        }

        return $movimiento->type === $tipo
            && $cuentaCoincide
            && $nodoCoincide
            && $movimiento->node_api_key_id === $nodeKey->id
            && $this->aCentavos($movimiento->amount) === $monto;
    }

    private function aCentavos(string|int|float $importe): int
    {
        $importe = number_format((float) $importe, 2, '.', '');
        [$pesos, $centavos] = explode('.', $importe);

        return ((int) $pesos * 100) + (int) $centavos;
    }

    private function aDecimal(int $centavos): string
    {
        return intdiv($centavos, 100)
            . '.'
            . str_pad(
                (string) ($centavos % 100),
                2,
                '0',
                STR_PAD_LEFT
            );
    }
}
