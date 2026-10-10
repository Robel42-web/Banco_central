<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminPanelController extends Controller
{
    public function dashboard()
    {
        $stats = [];

        $tables = [
            'branches' => 'branches',
            'atms' => 'atms',
            'accounts' => 'users_accounts',
            'transactions' => 'transactions',
        ];

        foreach ($tables as $key => $table) {
            try {
                $stats[$key] = Schema::hasTable($table)
                    ? DB::table($table)->count()
                    : 0;
            } catch (\Throwable $e) {
                report($e);
                $stats[$key] = 0;
            }
        }

        $recent = collect();

        try {
            if (Schema::hasTable('transactions')) {
                $recent = DB::table('transactions')
                    ->orderByDesc('created_at')
                    ->limit(8)
                    ->get([
                        'id',
                        'transaction_code',
                        'type',
                        'amount',
                        'status',
                        'branch_id',
                        'atm_id',
                        'created_at',
                    ]);
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return view('admin.dashboard', compact('stats', 'recent'));
    }

    public function accounts()
    {
        return $this->listing(
            'users_accounts',
            'Cuentas bancarias',
            'Consulta las cuentas registradas en el sistema central.',
            [
                'account_number' => 'Número de cuenta',
                'holder_name' => 'Titular',
                'balance' => 'Saldo',
                'status' => 'Estado',
                'created_at' => 'Registro',
            ]
        );
    }

    public function branches()
    {
        return $this->listing(
            'branches',
            'Sucursales',
            'Consulta las sucursales registradas y su efectivo disponible.',
            [
                'branch_code' => 'Código',
                'name' => 'Sucursal',
                'cash_available' => 'Efectivo disponible',
                'status' => 'Estado',
                'created_at' => 'Registro',
            ]
        );
    }

    public function atms()
    {
        return $this->listing(
            'atms',
            'Cajeros automáticos',
            'Consulta los cajeros registrados en el sistema central.',
            [
                'atm_code' => 'Código',
                'location' => 'Ubicación',
                'responsible_name' => 'Responsable',
                'branch_id' => 'Sucursal',
                'status' => 'Estado',
                'created_at' => 'Registro',
            ]
        );
    }

    public function transactions()
    {
        return $this->listing(
            'transactions',
            'Transacciones',
            'Consulta el historial de operaciones bancarias.',
            [
                'transaction_code' => 'Código',
                'type' => 'Tipo',
                'amount' => 'Importe',
                'status' => 'Estado',
                'branch_id' => 'Sucursal',
                'atm_id' => 'Cajero',
                'created_at' => 'Fecha',
            ],
            'created_at'
        );
    }

    private function listing(
        string $table,
        string $title,
        string $description,
        array $columns,
        ?string $order = null
    ) {
        $rows = collect();

        try {
            if (Schema::hasTable($table)) {
                $fields = array_keys($columns);

                $fields = array_values(array_filter(
                    $fields,
                    fn ($field) => Schema::hasColumn($table, $field)
                ));

                if (count($fields) > 0) {
                    $query = DB::table($table);

                    if ($order && Schema::hasColumn($table, $order)) {
                        $query->orderByDesc($order);
                    } elseif (Schema::hasColumn($table, 'created_at')) {
                        $query->orderByDesc('created_at');
                    }

                    $rows = $query->limit(100)->get($fields);
                }
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return view('admin.table', compact(
            'title',
            'description',
            'columns',
            'rows'
        ));
    }
}
