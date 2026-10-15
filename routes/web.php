<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminPanelController;
use App\Http\Controllers\Admin\NodeManagementController;
use App\Http\Middleware\AuthenticateAdmin;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

// Autenticación del administrador
Route::middleware('guest')->group(function () {
    Route::get('/admin/login', [AdminAuthController::class, 'showLogin'])
        ->name('admin.login');

    Route::post('/admin/login', [AdminAuthController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('admin.login.submit');
});

// Panel protegido del Banco Central
Route::middleware(AuthenticateAdmin::class)
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        // Panel principal
        Route::get('/', [AdminPanelController::class, 'dashboard'])
            ->name('dashboard');

        // Consultas y listados
        Route::get('/cuentas', [AdminPanelController::class, 'accounts'])
            ->name('accounts');

        Route::get('/sucursales', [AdminPanelController::class, 'branches'])
            ->name('branches');

        Route::get('/cajeros', [AdminPanelController::class, 'atms'])
            ->name('atms');

        Route::get('/transacciones', [AdminPanelController::class, 'transactions'])
            ->name('transactions');

        Route::get('/api-keys', [AdminPanelController::class, 'apiKeys'])
            ->name('api-keys');

        // Registrar sucursales
        Route::get('/sucursales/nueva', [
            NodeManagementController::class,
            'createBranch',
        ])->name('branches.create');

        Route::post('/sucursales', [
            NodeManagementController::class,
            'storeBranch',
        ])->name('branches.store');

        // Registrar cajeros
        Route::get('/cajeros/nuevo', [
            NodeManagementController::class,
            'createAtm',
        ])->name('atms.create');

        Route::post('/cajeros', [
            NodeManagementController::class,
            'storeAtm',
        ])->name('atms.store');

        // Revocar API Keys
        Route::post('/api-keys/{id}/revocar', [
            NodeManagementController::class,
            'revokeApiKey',
        ])->whereUuid('id')->name('api-keys.revoke');

        // Desactivar sucursales y cajeros
        Route::post('/sucursales/{id}/desactivar', [
            NodeManagementController::class,
            'deactivateBranch',
        ])->whereUuid('id')->name('branches.deactivate');

        Route::post('/cajeros/{id}/desactivar', [
            NodeManagementController::class,
            'deactivateAtm',
        ])->whereUuid('id')->name('atms.deactivate');

        // Cerrar sesión
        Route::post('/logout', [AdminAuthController::class, 'logout'])
            ->name('logout');
    });