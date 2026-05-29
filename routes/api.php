<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FincaController;
use App\Http\Controllers\Api\SolicitudRegistroController;
use App\Http\Controllers\Api\UsuarioController;
use App\Http\Middleware\EsAdministrador;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\EstimacionPesoController;
use App\Http\Controllers\Api\GanadoController;
use App\Http\Controllers\Api\CatalogoController;

/*
|--------------------------------------------------------------------------
| Módulo 1 — Gestión de Usuarios y Autenticación
|--------------------------------------------------------------------------
|
| Rutas públicas:  Login, recuperación de contraseña, envío de solicitud.
| Rutas protegidas (auth:sanctum): logout, perfil.
| Rutas de admin (auth:sanctum + EsAdministrador): CRUD usuarios y solicitudes.
|
*/

// Rutas de fincas y ganado (CRUD)
Route::middleware('auth:sanctum')->group(function () {

    Route::apiResource('fincas', FincaController::class);

    Route::get('ganado', [GanadoController::class, 'index']);
    Route::post('ganado', [GanadoController::class, 'store']);
    Route::get('ganado/{id}', [GanadoController::class, 'show']);
    Route::put('ganado/{id}', [GanadoController::class, 'update']);
    Route::delete('ganado/{id}', [GanadoController::class, 'destroy']);

    Route::get('catalogos/estados-salud', [CatalogoController::class, 'estadosSalud']);
    Route::get('catalogos/estados-comerciales', [CatalogoController::class, 'estadosComerciales']);

});


// ── Rutas públicas ────────────────────────────────────────────────────────────
Route::prefix('auth')->name('auth.')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('forgot-password');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('reset-password');
});

Route::post('/solicitudes', [SolicitudRegistroController::class, 'store'])->name('solicitudes.store');

// Estimacion de peso (público para desarrollo)
Route::prefix('estimacion')->group(function () {
    Route::get('/health', [EstimacionPesoController::class, 'healthCheck']);
    Route::post('/estimar', [EstimacionPesoController::class, 'estimar']);
    Route::post('/estimar-batch', [EstimacionPesoController::class, 'estimarBatch']);
});

// ── Rutas protegidas (usuario autenticado) ────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
    Route::get('/auth/me', [AuthController::class, 'me'])->name('auth.me');

    Route::middleware(EsAdministrador::class)->group(function () {
        Route::apiResource('usuarios', UsuarioController::class)
            ->only(['index', 'show', 'store', 'update', 'destroy']);

        Route::get('/solicitudes', [SolicitudRegistroController::class, 'index'])->name('solicitudes.index');
        Route::get('/solicitudes/pendientes', [SolicitudRegistroController::class, 'pendientes'])->name('solicitudes.pendientes');
        Route::get('/solicitudes/{id}', [SolicitudRegistroController::class, 'show'])->name('solicitudes.show');
        Route::put('/solicitudes/{id}/revisar', [SolicitudRegistroController::class, 'revisar'])->name('solicitudes.revisar');
    });
});