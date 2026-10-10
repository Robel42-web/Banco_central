<?php
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminPanelController;
use App\Http\Middleware\AuthenticateAdmin;
use Illuminate\Support\Facades\Route;
Route::redirect('/','/admin');
Route::middleware('guest')->group(function(){
 Route::get('/admin/login',[AdminAuthController::class,'showLogin'])->name('admin.login');
 Route::post('/admin/login',[AdminAuthController::class,'login'])->middleware('throttle:5,1')->name('admin.login.submit');
});
Route::middleware(AuthenticateAdmin::class)->prefix('admin')->name('admin.')->group(function(){
 Route::get('/',[AdminPanelController::class,'dashboard'])->name('dashboard');
 Route::get('/cuentas',[AdminPanelController::class,'accounts'])->name('accounts');
 Route::get('/sucursales',[AdminPanelController::class,'branches'])->name('branches');
 Route::get('/cajeros',[AdminPanelController::class,'atms'])->name('atms');
 Route::get('/transacciones',[AdminPanelController::class,'transactions'])->name('transactions');
 Route::post('/logout',[AdminAuthController::class,'logout'])->name('logout');
});
