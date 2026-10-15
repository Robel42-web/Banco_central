<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

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
        $data = $request->validate([
            'branch_code' => [
                'required', 'string', 'max:20',
                Rule::unique('branches', 'branch_code'),
            ],
            'name' => ['required', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:2000'],
            'responsible_name' => ['nullable', 'string', 'max:150'],
            'cash_available' => [
                'required', 'numeric', 'min:0', 'max:999999999999.99',
            ],
        ]);

        $apiKey = 'branch_' . bin2hex(random_bytes(32));

        $branchId = (string) Str::uuid();

        DB::transaction(function () use ($data, $apiKey, $branchId) {
            DB::table('branches')->insert([
                'id' => $branchId,
                'branch_code' => $data['branch_code'],
                'name' => $data['name'],
                'address' => $data['address'] ?? null,
                'responsible_name' => $data['responsible_name'] ?? null,
                'cash_available' => $data['cash_available'],
                'status' => 'active',
            ]);

            $this->saveApiKey(
                $apiKey,
                'branch',
                $branchId,
                null,
                'API Key para sucursal ' . $data['branch_code']
            );
        });

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
                'required', 'string', 'max:20',
                Rule::unique('atms', 'atm_code'),
            ],
            'branch_id' => [
                'nullable', 'uuid',
                Rule::exists('branches', 'id')->where('status', 'active'),
            ],
            'location' => ['nullable', 'string', 'max:2000'],
            'responsible_name' => ['nullable', 'string', 'max:150'],
            'reported_cash_available' => [
                'required', 'numeric', 'min:0', 'max:999999999999.99',
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
                'responsible_name' => $data['responsible_name'] ?? null,
                'reported_cash_available' => $data['reported_cash_available'],
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
            'nodeName' => $data['location'] ?: $data['atm_code'],
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
                'api_key' => 'La clave no existe, ya fue revocada o no pudo modificarse.',
            ]);
        }

        return back()->with(
            'success',
            'La API Key fue revocada correctamente.'
        );
    }

    
    public function deactivateBranch(string $id)
    {
        $branch = DB::table('branches')->where('id', $id)->first();

        if (!$branch) {
            return back()->withErrors([
                'node' => 'La sucursal no existe.',
            ]);
        }

        if ($branch->status === 'inactive') {
            return back()->with('success', 'La sucursal ya está inactiva.');
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
        $atm = DB::table('atms')->where('id', $id)->first();

        if (!$atm) {
            return back()->withErrors([
                'node' => 'El cajero no existe.',
            ]);
        }

        if ($atm->status === 'inactive') {
            return back()->with('success', 'El cajero ya está inactivo.');
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
