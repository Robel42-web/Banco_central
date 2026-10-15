@extends('admin.layout')

@section('title', 'Nuevo cajero ATM')
@section('heading', 'Registrar cajero ATM')

@section('content')
<div class="card">
    <p class="muted">
        Registra el cajero en el sistema central y genera su API Key.
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

    <form method="POST" action="{{ route('admin.atms.store') }}">
        @csrf

        <p>
            <label for="atm_code">Código del cajero *</label><br>
            <input id="atm_code" name="atm_code"
                value="{{ old('atm_code') }}" placeholder="ATM-001"
                maxlength="20" required
                style="width:100%;padding:11px;margin-top:6px">
        </p>

        <p>
            <label for="branch_id">Sucursal asociada</label><br>
            <select id="branch_id" name="branch_id"
                style="width:100%;padding:11px;margin-top:6px">
                <option value="">Sin sucursal asociada</option>
                @foreach ($branches as $branch)
                    <option value="{{ $branch->id }}"
                        @selected(old('branch_id') == $branch->id)>
                        {{ $branch->branch_code }} - {{ $branch->name }}
                    </option>
                @endforeach
            </select>
        </p>

        <p>
            <label for="location">Ubicación</label><br>
            <textarea id="location" name="location" rows="3"
                style="width:100%;padding:11px;margin-top:6px">{{ old('location') }}</textarea>
        </p>

        <p>
            <label for="responsible_name">Responsable</label><br>
            <input id="responsible_name" name="responsible_name"
                value="{{ old('responsible_name') }}" maxlength="150"
                style="width:100%;padding:11px;margin-top:6px">
        </p>

        <p>
            <label for="reported_cash_available">Efectivo inicial (MXN) *</label><br>
            <input id="reported_cash_available"
                name="reported_cash_available" type="number"
                step="0.01" min="0"
                value="{{ old('reported_cash_available', '0.00') }}"
                required style="width:100%;padding:11px;margin-top:6px">
        </p>

        <button type="submit">Registrar y generar API Key</button>
        <a class="button-link" href="{{ route('admin.atms') }}">
            Cancelar
        </a>
    </form>
</div>
@endsection
