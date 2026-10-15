@extends('admin.layout')

@section('title', 'Nueva sucursal')
@section('heading', 'Registrar sucursal')

@section('content')
<div class="card">
    <p class="muted">
        Registra una sucursal en el Banco Central y genera su API Key.
    </p>

    @if ($errors->any())
        <div class="card" style="border-color:#dc2626;color:#991b1b">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.branches.store') }}">
        @csrf

        <p>
            <label for="branch_code">Código de sucursal *</label><br>
            <input id="branch_code" name="branch_code"
                value="{{ old('branch_code') }}"
                placeholder="SUC-002" maxlength="20" required
                style="width:100%;padding:11px;margin-top:6px">
        </p>

        <p>
            <label for="name">Nombre *</label><br>
            <input id="name" name="name" value="{{ old('name') }}"
                maxlength="120" required
                style="width:100%;padding:11px;margin-top:6px">
        </p>

        <p>
            <label for="address">Dirección</label><br>
            <textarea id="address" name="address" rows="3"
                style="width:100%;padding:11px;margin-top:6px">{{ old('address') }}</textarea>
        </p>

        <p>
            <label for="responsible_name">Responsable</label><br>
            <input id="responsible_name" name="responsible_name"
                value="{{ old('responsible_name') }}" maxlength="150"
                style="width:100%;padding:11px;margin-top:6px">
        </p>

        <p>
            <label for="cash_available">Efectivo inicial (MXN) *</label><br>
            <input id="cash_available" name="cash_available"
                type="number" step="0.01" min="0"
                value="{{ old('cash_available', '0.00') }}" required
                style="width:100%;padding:11px;margin-top:6px">
        </p>

        <button type="submit">Registrar y generar API Key</button>
        <a class="button-link" href="{{ route('admin.branches') }}">
            Cancelar
        </a>
    </form>
</div>
@endsection
