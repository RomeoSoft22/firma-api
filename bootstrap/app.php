<?php

use App\Exceptions\ErroresSeguros;
use App\Http\Middleware\CabecerasSeguridad;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(CabecerasSeguridad::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Toda respuesta en JSON (incluye errores de validación 422) y sin detalles internos
        // (SQL, clases, motor), ni siquiera con APP_DEBUG=true. Ver ErroresSeguros.
        ErroresSeguros::registrar($exceptions);
    })->create();
