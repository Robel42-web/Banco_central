<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CuentaController;
use App\Http\Controllers\Api\OperacionController;
use App\Http\Middleware\AuthenticateNodeApiKey;

Route::get('/salud', function () {
    return response()->json([
        'servicio' => 'Banco Central',
        'estado' => 'OK',
    ]);
});

// Los endpoints bancarios requieren una API Key válida.
Route::middleware(AuthenticateNodeApiKey::class)->group(
    function () {
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
    }
);