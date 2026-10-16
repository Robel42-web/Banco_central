@extends('admin.layout')

@section('title', 'Nueva sucursal')
@section('heading', 'Registrar sucursal')

@section('content')
<div class="card">
    <p class="muted">
        Registra una sucursal en el Banco Central y crea
        las credenciales de acceso de su administrador.
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

        <h3>Datos de la sucursal</h3>

        <p>
            <label for="branch_code">Código de sucursal *</label><br>
            <input id="branch_code" name="branch_code"
                value="{{ old('branch_code') }}"
                placeholder="SUC-002" maxlength="20" required
                style="width:100%;padding:11px;margin-top:6px">
        </p>

        <p>
            <label for="name">Nombre *</label><br>
            <input id="name" name="name"
                value="{{ old('name') }}"
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
                value="{{ old('responsible_name') }}"
                maxlength="150"
                style="width:100%;padding:11px;margin-top:6px">
        </p>

        <p>
            <label for="cash_available">Efectivo inicial (MXN) *</label><br>
            <input id="cash_available" name="cash_available"
                type="number" step="0.01" min="0"
                value="{{ old('cash_available', '0.00') }}" required
                style="width:100%;padding:11px;margin-top:6px">
        </p>

        <hr>

        <h3>Acceso del administrador</h3>

        <p class="muted">
            Estas credenciales se crearán en el proyecto Supabase
            de Sucursales y quedarán vinculadas a esta sucursal.
        </p>

        <p>
            <label for="admin_email">Correo del administrador *</label><br>
            <input id="admin_email" name="admin_email"
                type="email"
                value="{{ old('admin_email') }}"
                maxlength="254" required autocomplete="off"
                style="width:100%;padding:11px;margin-top:6px">
        </p>

        <p>
            <label for="admin_password">Contraseña inicial *</label><br>
            <input id="admin_password" name="admin_password"
                type="password" minlength="12" required
                autocomplete="new-password"
                style="width:100%;padding:11px;margin-top:6px">
            <small>
                Utiliza una contraseña de al menos 12 caracteres.
                Comunícala al administrador de forma segura.
            </small>
        </p>

        <p>
            <label for="admin_password_confirmation">
                Confirmar contraseña inicial *
            </label><br>
            <input id="admin_password_confirmation"
                name="admin_password_confirmation"
                type="password" minlength="12" required
                autocomplete="new-password"
                style="width:100%;padding:11px;margin-top:6px">
        </p>

        <button type="submit">
            Registrar sucursal y crear acceso
        </button>

        <a class="button-link" href="{{ route('admin.branches') }}">
            Cancelar
        </a>
    </form>
</div>
@endsection
