<?php

use App\Http\Controllers\Api\DocumentoController;
use App\Http\Controllers\Api\LoteController;
use App\Http\Controllers\Api\MotivoController;
use App\Http\Controllers\Api\ParametroController;
use Illuminate\Support\Facades\Route;

// Límite de peticiones por IP para evitar abuso (subidas masivas, fuerza bruta de IDs).
Route::middleware('throttle:300,1')->group(function () {
    Route::get('estado', fn () => ['estado' => 'ok']);

    Route::get('documentos/estadisticas', [DocumentoController::class, 'estadisticas']);
    Route::get('documentos/hash/{hash}', [DocumentoController::class, 'buscarPorHash'])
        ->where('hash', '[A-Fa-f0-9]{64}');
    Route::post('documentos/{documento}/firmado', [DocumentoController::class, 'registrarFirma'])->whereNumber('documento');
    Route::post('documentos/{documento}/error', [DocumentoController::class, 'registrarError'])->whereNumber('documento');
    Route::get('documentos/{documento}/descargar/{tipo}', [DocumentoController::class, 'descargar'])
        ->whereNumber('documento')
        ->whereIn('tipo', ['original', 'firmado']);
    Route::apiResource('documentos', DocumentoController::class)->except('update')->whereNumber('documento');

    Route::apiResource('lotes', LoteController::class)->only(['index', 'store', 'show'])->whereNumber('lote');

    Route::get('motivos/{motivo}/imagen', [MotivoController::class, 'imagen'])->whereNumber('motivo');
    Route::post('motivos/{motivo}/imagen', [MotivoController::class, 'subirImagen'])->whereNumber('motivo');
    Route::delete('motivos/{motivo}/imagen', [MotivoController::class, 'eliminarImagen'])->whereNumber('motivo');
    Route::apiResource('motivos', MotivoController::class)->except('show')->whereNumber('motivo');

    Route::get('parametros', [ParametroController::class, 'index']);
    Route::put('parametros', [ParametroController::class, 'update']);
});
