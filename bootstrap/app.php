<?php

use App\Http\Middleware\CabecerasSeguridad;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

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
        // Toda respuesta en JSON (incluye errores de validación 422) y sin detalles internos:
        // con APP_DEBUG=false Laravel no expone excepciones, clases ni trazas.
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => true);

        // Los mensajes de las excepciones HTTP pueden incluir nombres de clases internas
        // (p. ej. "No query results for model [App\Models\...]"): se reemplazan por textos genéricos.
        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            $mensajes = [
                403 => 'Operación no autorizada.',
                404 => 'El recurso solicitado no existe.',
                405 => 'Operación no permitida.',
                413 => 'El archivo excede el tamaño permitido.',
                429 => 'Demasiadas solicitudes. Intente nuevamente en un momento.',
            ];
            $codigo = $e->getStatusCode();

            return response()->json(
                ['message' => $mensajes[$codigo] ?? 'No se pudo procesar la solicitud.'],
                $codigo,
                $e->getHeaders(),
            );
        });
    })->create();
