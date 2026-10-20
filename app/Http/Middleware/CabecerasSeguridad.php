<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cabeceras de seguridad para todas las respuestas y ocultamiento de la tecnología del servidor.
 */
class CabecerasSeguridad
{
    public function handle(Request $request, Closure $next): Response
    {
        $respuesta = $next($request);

        if (function_exists('header_remove')) {
            header_remove('X-Powered-By');
        }
        $respuesta->headers->remove('X-Powered-By');
        $respuesta->headers->remove('Server');

        $respuesta->headers->set('X-Content-Type-Options', 'nosniff');
        $respuesta->headers->set('X-Frame-Options', 'DENY');
        $respuesta->headers->set('Referrer-Policy', 'no-referrer');
        $respuesta->headers->set('Cross-Origin-Resource-Policy', 'cross-origin');
        $respuesta->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $respuesta->headers->set('Content-Security-Policy', "default-src 'none'; frame-ancestors 'none'");

        return $respuesta;
    }
}
