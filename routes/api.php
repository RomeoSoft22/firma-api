<?php

use App\Http\Controllers\Api\AccesoController;
use App\Http\Controllers\Api\DocumentoController;
use App\Http\Controllers\Api\LoteController;
use App\Http\Controllers\Api\MotivoController;
use App\Http\Controllers\Api\ParametroController;
use App\Http\Middleware\RequiereAcceso;
use Illuminate\Support\Facades\Route;

// Límite general de peticiones por IP (subidas masivas, recorrido de IDs, etc.).
Route::middleware('throttle:300,1')->group(function () {
    Route::get('estado', fn () => ['estado' => 'ok']);

    // ---------------------------------------------------------------- Acceso de administración
    // El ingreso ocurre en identidad.juarezcampos.net (SSO). Aquí solo se canjea el código,
    // se renueva el token con la cookie HttpOnly y se arma la URL de salida.
    Route::get('acceso/config', [AccesoController::class, 'config']);
    Route::post('acceso/canjear', [AccesoController::class, 'canjear'])->middleware('throttle:20,1');
    Route::post('acceso/renovar', [AccesoController::class, 'renovar'])->middleware('throttle:60,1');
    Route::post('acceso/salir', [AccesoController::class, 'salir']);

    // ---------------------------------------------------------------- Público: flujo de firma
    // Subir el original devuelve un token de carga; solo con él se adjunta el firmado o el error.
    Route::post('documentos', [DocumentoController::class, 'store']);
    Route::post('documentos/{documento}/firmado', [DocumentoController::class, 'registrarFirma'])->whereNumber('documento');
    Route::post('documentos/{documento}/error', [DocumentoController::class, 'registrarError'])->whereNumber('documento');
    Route::post('lotes', [LoteController::class, 'store']);
    // Verificación: confirma si un PDF firmado fue emitido por el sistema (datos mínimos).
    Route::get('documentos/hash/{hash}', [DocumentoController::class, 'buscarPorHash'])->where('hash', '[A-Fa-f0-9]{64}');
    // Catálogos que usa la pantalla de firma (solo lectura).
    Route::get('motivos', [MotivoController::class, 'activos']);
    Route::get('motivos/{motivo}/imagen', [MotivoController::class, 'imagen'])->whereNumber('motivo');
    Route::get('parametros', [ParametroController::class, 'index']);

    // ---------------------------------------------------------------- Protegido: Historial y Configuración (JWT de identidad con rol ADMIN en FIRMA)
    Route::middleware(RequiereAcceso::class)->group(function () {
        Route::get('acceso/yo', [AccesoController::class, 'yo']);

        Route::get('documentos', [DocumentoController::class, 'index']);
        Route::get('documentos/estadisticas', [DocumentoController::class, 'estadisticas']);
        Route::get('documentos/{documento}', [DocumentoController::class, 'show'])->whereNumber('documento');
        Route::delete('documentos/{documento}', [DocumentoController::class, 'destroy'])->whereNumber('documento');
        Route::get('documentos/{documento}/descargar/{tipo}', [DocumentoController::class, 'descargar'])
            ->whereNumber('documento')
            ->whereIn('tipo', ['original', 'firmado']);

        Route::get('lotes', [LoteController::class, 'index']);
        Route::get('lotes/{lote}', [LoteController::class, 'show'])->whereNumber('lote');

        Route::get('motivos/todos', [MotivoController::class, 'index']);
        Route::post('motivos', [MotivoController::class, 'store']);
        Route::put('motivos/{motivo}', [MotivoController::class, 'update'])->whereNumber('motivo');
        Route::delete('motivos/{motivo}', [MotivoController::class, 'destroy'])->whereNumber('motivo');
        Route::post('motivos/{motivo}/imagen', [MotivoController::class, 'subirImagen'])->whereNumber('motivo');
        Route::delete('motivos/{motivo}/imagen', [MotivoController::class, 'eliminarImagen'])->whereNumber('motivo');

        Route::put('parametros', [ParametroController::class, 'update']);
    });
});
