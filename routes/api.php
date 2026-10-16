<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CuentaController;
use App\Http\Controllers\Api\OperacionController;
use App\Http\Controllers\Api\SucursalAuthController;
use App\Http\Middleware\AuthenticateNodeApiKey;

/*
|--------------------------------------------------------------------------
| Rutas públicas
|--------------------------------------------------------------------------
*/

Route::get('/salud', function () {
    return response()->json([
        'servicio' => 'Banco Central',
        'estado' => 'OK',
    ]);
});

// Inicio de sesión de Sucursales.
// No necesita una API Key previa.
Route::post(
    '/sucursales/login',
    [SucursalAuthController::class, 'login']
)->middleware('throttle:5,1');

/*
|--------------------------------------------------------------------------
| Rutas protegidas por API Key
|--------------------------------------------------------------------------
*/

Route::middleware(AuthenticateNodeApiKey::class)->group(function () {

    Route::post('/cuentas', [
        CuentaController::class,
        'store',
    ]);

    Route::get('/cuentas/numero/{numeroCuenta}', [
        CuentaController::class,
        'showPorNumero',
    ]);

    Route::get('/cuentas/{id}', [
        CuentaController::class,
        'show',
    ]);

    Route::get('/cuentas/{id}/saldo', [
        CuentaController::class,
        'saldo',
    ]);

    Route::get('/cuentas/{id}/movimientos', [
        CuentaController::class,
        'movimientos',
    ]);

    Route::post('/operaciones/depositos', [
        OperacionController::class,
        'deposito',
    ]);

    Route::post('/operaciones/retiros', [
        OperacionController::class,
        'retiro',
    ]);

    // Validar una sucursal que ya cuenta con API Key.
    Route::post(
        '/sucursales/validar-acceso',
        [SucursalAuthController::class, 'validarAcceso']
    );
});
