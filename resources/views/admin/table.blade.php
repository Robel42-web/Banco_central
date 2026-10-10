
@extends('admin.layout')

@section('title', $title)
@section('header', $title)

@section('content')
    <div class="page-heading">
        <div>
            <h2>{{ $title }}</h2>
            <p>{{ $description }}</p>
        </div>
        <span class="badge">{{ $rows->count() }} registros en esta página</span>
    </div>

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
                </tr>
                </thead>

                <tbody>
                @forelse ($rows as $row)
                    <tr data-searchable>
                        @foreach ($columns as $field => $label)
                            @php
                                $value = $row->{$field} ?? null;
                                $status = strtolower((string) ($field === 'status' ? $value : ''));
                                $isMoney = in_array($field, ['balance', 'cash_available', 'amount', 'reported_cash_available'], true);
                                $isUuid = in_array($field, ['branch_id', 'atm_id', 'id'], true);
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
                                        ][$status] ?? ucfirst($status ?: 'Sin estado') }}
                                    </span>
                                @elseif ($isMoney && is_numeric($value))
                                    ${{ number_format((float) $value, 2) }} MXN
                                @elseif ($isUuid && $value)
                                    <span title="{{ $value }}">
                                        {{ \Illuminate\Support\Str::limit((string) $value, 13) }}
                                    </span>
                                @elseif ($field === 'created_at' && $value)
                                    {{ \Illuminate\Support\Carbon::parse($value)->format('d/m/Y H:i') }}
                                @elseif (is_null($value) || $value === '')
                                    <span class="muted">—</span>
                                @else
                                    {{ $value }}
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ max(count($columns), 1) }}" class="empty">
                            No hay registros para mostrar.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="table-footer">
            Se muestran hasta 100 registros por módulo. La búsqueda filtra los registros
            cargados en esta página; no consulta toda la base de datos.
        </div>
    </section>
@endsection
