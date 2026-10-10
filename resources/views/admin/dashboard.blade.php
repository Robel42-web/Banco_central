
@extends('admin.layout')

@section('title', 'Resumen')
@section('header', 'Panel de control')

@section('content')
    <div class="page-heading">
        <div>
            <h2>Resumen general</h2>
            <p>Consulta el estado de los recursos registrados en la base central.</p>
        </div>
        <span class="badge">Vista administrativa</span>
    </div>

    <div class="grid stats-grid">
        <article class="card">
            <div class="stat-top">
                <span class="stat-label">Sucursales registradas</span>
                <span class="stat-icon">▥</span>
            </div>
            <div class="stat-value">
                {{ number_format($stats['branches'] ?? 0) }}
            </div>
            <div class="stat-note">Registros en la base central</div>
        </article>

        <article class="card">
            <div class="stat-top">
                <span class="stat-label">Cajeros registrados</span>
                <span class="stat-icon">▣</span>
            </div>
            <div class="stat-value">
                {{ number_format($stats['atms'] ?? 0) }}
            </div>
            <div class="stat-note">Dispositivos registrados en el sistema</div>
        </article>

        <article class="card">
            <div class="stat-top">
                <span class="stat-label">Cuentas bancarias</span>
                <span class="stat-icon">▤</span>
            </div>
            <div class="stat-value">
                {{ number_format($stats['accounts'] ?? 0) }}
            </div>
            <div class="stat-note">Cuentas registradas en el sistema</div>
        </article>

        <article class="card">
            <div class="stat-top">
                <span class="stat-label">Transacciones</span>
                <span class="stat-icon">⇄</span>
            </div>
            <div class="stat-value">
                {{ number_format($stats['transactions'] ?? 0) }}
            </div>
            <div class="stat-note">Operaciones registradas históricamente</div>
        </article>
    </div>

    <div class="grid two-columns" style="margin-top: 22px;">
        <section class="card table-card">
            <div class="table-toolbar">
                <div>
                    <h3>Transacciones recientes</h3>
                    <p>Últimos registros encontrados en la base central</p>
                </div>
                <a class="button" href="{{ route('admin.transactions') }}">
                    Ver historial →
                </a>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>Código</th>
                        <th>Tipo</th>
                        <th>Importe</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($recent as $transaction)
                        @php
                            $status = strtolower((string) $transaction->status);
                            $typeLabels = [
                                'deposit' => 'Depósito',
                                'withdrawal' => 'Retiro',
                                'transfer' => 'Transferencia',
                            ];
                            $statusLabels = [
                                'completed' => 'Completada',
                                'pending' => 'Pendiente',
                                'rejected' => 'Rechazada',
                            ];
                        @endphp
                        <tr>
                            <td class="primary-cell">
                                {{ $transaction->transaction_code }}
                            </td>
                            <td>
                                {{ $typeLabels[$transaction->type] ?? $transaction->type }}
                            </td>
                            <td>
                                ${{ number_format((float) $transaction->amount, 2) }}
                                <span class="muted">MXN</span>
                            </td>
                            <td>
                                <span class="badge {{ $status }}">
                                    {{ $statusLabels[$status] ?? ucfirst($status) }}
                                </span>
                            </td>
                            <td>
                                {{ $transaction->created_at
                                    ? \Illuminate\Support\Carbon::parse($transaction->created_at)->format('d/m/Y H:i')
                                    : '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="empty">
                                No hay transacciones registradas todavía.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card">
            <div class="card-heading">
                <div>
                    <h3>Accesos rápidos</h3>
                    <p>Consulta los principales módulos</p>
                </div>
            </div>

            <a href="{{ route('admin.accounts') }}" class="status-row">
                <span class="status-name">
                    <span class="stat-icon">▤</span>
                    <span>
                        <strong>Cuentas bancarias</strong><br>
                        <span class="muted">Consultar titulares y saldos</span>
                    </span>
                </span>
                <span>→</span>
            </a>

            <a href="{{ route('admin.branches') }}" class="status-row">
                <span class="status-name">
                    <span class="stat-icon">▥</span>
                    <span>
                        <strong>Sucursales</strong><br>
                        <span class="muted">Consultar sucursales y efectivo</span>
                    </span>
                </span>
                <span>→</span>
            </a>

            <a href="{{ route('admin.atms') }}" class="status-row">
                <span class="status-name">
                    <span class="stat-icon">▣</span>
                    <span>
                        <strong>Cajeros ATM</strong><br>
                        <span class="muted">Consultar dispositivos registrados</span>
                    </span>
                </span>
                <span>→</span>
            </a>

            <a href="{{ route('admin.transactions') }}" class="status-row">
                <span class="status-name">
                    <span class="stat-icon">⇄</span>
                    <span>
                        <strong>Transacciones</strong><br>
                        <span class="muted">Revisar el historial de operaciones</span>
                    </span>
                </span>
                <span>→</span>
            </a>

            <div style="margin-top: 20px; padding: 13px; background: #f4f8fc; border-radius: 9px;">
                <strong style="font-size: 12px;">Información</strong>
                <p class="muted" style="font-size: 11px; line-height: 1.6; margin-bottom: 0;">
                    Los indicadores muestran cantidades de registros.
                    No representan necesariamente nodos activos ni disponibilidad en tiempo real.
                </p>
            </div>
        </section>
    </div>
@endsection
