<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cuenta;
use App\Models\Movimiento;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CuentaController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $nodo = $request->attributes->get('node_api_key');

        // Solo una sucursal puede crear cuentas.
        if (!$nodo || $nodo->node_type !== 'branch') {
            return response()->json([
                'mensaje' => 'Solo una sucursal puede crear cuentas',
            ], 403);
        }

        $datos = $request->validate([
            'titular' => ['required', 'string', 'max:150'],
            'numero_cuenta' => [
                'required',
                'string',
                'max:30',
                Rule::unique('users_accounts', 'account_number'),
            ],
        ]);

        $cuenta = Cuenta::create([
            'holder_name' => $datos['titular'],
            'account_number' => $datos['numero_cuenta'],
            'balance' => '0.00',
            'status' => 'active',
            'branch_id' => $nodo->branch_id,
        ]);

        return response()->json([
            'mensaje' => 'Cuenta creada correctamente',
            'cuenta' => $this->formatearCuenta($cuenta),
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $cuenta = Cuenta::find($id);

        if (!$cuenta) {
            return response()->json([
                'mensaje' => 'Cuenta no encontrada',
            ], 404);
        }

        return response()->json([
            'cuenta' => $this->formatearCuenta($cuenta),
        ]);
    }

    public function showPorNumero(
        string $numeroCuenta
    ): JsonResponse {
        $cuenta = Cuenta::where(
            'account_number',
            $numeroCuenta
        )->first();

        if (!$cuenta) {
            return response()->json([
                'mensaje' => 'Cuenta no encontrada',
            ], 404);
        }

        return response()->json([
            'cuenta' => $this->formatearCuenta($cuenta),
        ]);
    }

    public function saldo(string $id): JsonResponse
    {
        $cuenta = Cuenta::find($id);

        if (!$cuenta) {
            return response()->json([
                'mensaje' => 'Cuenta no encontrada',
            ], 404);
        }

        $centavos = $this->aCentavos($cuenta->balance);

        return response()->json([
            'cuenta_id' => $cuenta->id,
            'numero_cuenta' => $cuenta->account_number,
            'saldo_centavos' => $centavos,
            'saldo' => $this->aPesos($centavos),
            'moneda' => 'MXN',
            'estado' => $cuenta->status,
        ]);
    }

    public function movimientos(string $id): JsonResponse
    {
        $cuenta = Cuenta::find($id);

        if (!$cuenta) {
            return response()->json([
                'mensaje' => 'Cuenta no encontrada',
            ], 404);
        }

        $movimientos = Movimiento::query()
            ->where('account_origin_id', $cuenta->id)
            ->orWhere('account_destination_id', $cuenta->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($movimientos);
    }

    private function formatearCuenta(Cuenta $cuenta): array
    {
        $centavos = $this->aCentavos($cuenta->balance);

        return [
            'id' => $cuenta->id,
            'numero_cuenta' => $cuenta->account_number,
            'titular' => $cuenta->holder_name,
            'saldo_centavos' => $centavos,
            'saldo' => $this->aPesos($centavos),
            'estado' => $cuenta->status,
            'moneda' => 'MXN',
        ];
    }

    private function aCentavos(string|int|float $importe): int
    {
        $importe = number_format((float) $importe, 2, '.', '');
        [$pesos, $centavos] = explode('.', $importe);

        return ((int) $pesos * 100) + (int) $centavos;
    }

    private function aPesos(int $centavos): string
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