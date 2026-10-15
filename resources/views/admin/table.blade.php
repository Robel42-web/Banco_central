
@extends('admin.layout')

@section('title', $title)
@section('heading', $title)

@section('content')
    <div class="page-heading">
        <div>
            <h2>{{ $title }}</h2>
            <p>{{ $description }}</p>
        </div>
        <span class="badge">{{ $rows->count() }} registros en esta página</span>
    </div>

    @if ($errors->any())
        <div class="card" role="alert" style="border-color:#dc2626;color:#991b1b">
            <strong>No se pudo completar la acción:</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="card table-card">
        <div class="table-toolbar">
            <div>
                <h3>Listado de registros</h3>
                <p>Busca por cualquier dato visible de la tabla.</p>
            </div>

            <input
                type="search"
                class="search-input"
                placeholder="Buscar registros..."
                aria-label="Buscar registros"
                data-table-search="adminDataTable"
            >
        </div>

        <div class="table-wrap">
            <table id="adminDataTable">
                <thead>
                    <tr>
                        @foreach ($columns as $field => $label)
                            <th>{{ $label }}</th>
                        @endforeach

                        @if (in_array($title, [
                            'Sucursales',
                            'Cajeros automáticos',
                            'API Keys',
                        ], true))
                            <th>Acciones</th>
                        @endif
                    </tr>
                </thead>

                <tbody>
                    @forelse ($rows as $row)
                        <tr data-searchable>
                            @foreach ($columns as $field => $label)
                                @php
                                    $value = $row->{$field} ?? null;
                                    $status = strtolower(
                                        (string) ($field === 'status' ? $value : '')
                                    );

                                    $isMoney = in_array($field, [
                                        'balance',
                                        'cash_available',
                                        'amount',
                                        'reported_cash_available',
                                    ], true);

                                    $isUuid = in_array($field, [
                                        'branch_id',
                                        'atm_id',
                                    ], true);
                                @endphp

                                <td>
                                    @if ($field === 'status')
                                        <span class="badge {{ $status }}">
                                            {{ [
                                                'active' => 'Activo',
                                                'inactive' => 'Inactivo',
                                                'suspended' => 'Suspendido',
                                                'blocked' => 'Bloqueada',
                                                'closed' => 'Cerrada',
                                                'maintenance' => 'Mantenimiento',
                                                'completed' => 'Completada',
                                                'pending' => 'Pendiente',
                                                'rejected' => 'Rechazada',
                                                'revoked' => 'Revocada',
                                            ][$status] ?? ucfirst($status ?: 'Sin estado') }}
                                        </span>

                                    @elseif ($field === 'node_type')
                                        {{ [
                                            'branch' => 'Sucursal',
                                            'atm' => 'Cajero ATM',
                                        ][$value] ?? $value }}

                                    @elseif ($isMoney && is_numeric($value))
                                        ${{ number_format((float) $value, 2) }} MXN

                                    @elseif ($isUuid && $value)
                                        <span title="{{ $value }}">
                                            {{ \Illuminate\Support\Str::limit((string) $value, 13) }}
                                        </span>

                                    @elseif (in_array($field, [
                                        'created_at',
                                        'last_reported_at',
                                        'last_used_at',
                                        'expires_at',
                                    ], true) && $value)
                                        {{ \Illuminate\Support\Carbon::parse($value)->format('d/m/Y H:i') }}

                                    @elseif (is_null($value) || $value === '')
                                        <span class="muted">—</span>

                                    @else
                                        {{ $value }}
                                    @endif
                                </td>
                            @endforeach

                            @if (in_array($title, [
                                'Sucursales',
                                'Cajeros automáticos',
                                'API Keys',
                            ], true))
                                <td>
                                    @if (
                                        $title === 'Sucursales'
                                        && $row->status === 'active'
                                    )
                                        <form
                                            method="POST"
                                            action="{{ route('admin.branches.deactivate', $row->id) }}"
                                            onsubmit="return confirm('¿Desactivar esta sucursal y revocar sus API Keys activas?')"
                                        >
                                            @csrf
                                            <button type="submit" class="action-button">
                                                Desactivar
                                            </button>
                                        </form>

                                    @elseif (
                                        $title === 'Cajeros automáticos'
                                        && $row->status !== 'inactive'
                                    )
                                        <form
                                            method="POST"
                                            action="{{ route('admin.atms.deactivate', $row->id) }}"
                                            onsubmit="return confirm('¿Desactivar este cajero y revocar sus API Keys activas?')"
                                        >
                                            @csrf
                                            <button type="submit" class="action-button">
                                                Desactivar
                                            </button>
                                        </form>

                                    @elseif (
                                        $title === 'API Keys'
                                        && $row->status === 'active'
                                    )
                                        <form
                                            method="POST"
                                            action="{{ route('admin.api-keys.revoke', $row->id) }}"
                                            onsubmit="return confirm('¿Revocar esta API Key? El nodo dejará de autenticarse con ella.')"
                                        >
                                            @csrf
                                            <button type="submit" class="action-button">
                                                Revocar clave
                                            </button>
                                        </form>

                                    @else
                                        <span class="muted">Sin acciones</span>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td
                                colspan="{{ count($columns) + (in_array($title, ['Sucursales', 'Cajeros automáticos', 'API Keys'], true) ? 1 : 0) }}"
                                class="empty"
                            >
                                No hay registros para mostrar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="table-footer">
            Se muestran hasta 100 registros por módulo. La búsqueda filtra los
            registros cargados en esta página; no consulta toda la base de datos.
        </div>
    </section>
@endsection
