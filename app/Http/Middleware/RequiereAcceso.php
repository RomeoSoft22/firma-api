<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exige una sesión de administración válida (Authorization: Bearer <token>) obtenida con la
 * clave de ADMIN_CLAVE. Solo se guarda el hash del token; la sesión se renueva con cada uso.
 */
class RequiereAcceso
{
    public static function llave(string $token): string
    {
        return 'acceso-admin:sesion:'.hash('sha256', $token);
    }

    public function handle(Request $request, Closure $next): Response
    {
        $token = (string) $request->bearerToken();
        $llave = self::llave($token);

        if (strlen($token) !== 64 || ! Cache::has($llave)) {
            return response()->json(['message' => 'Ingrese la clave de administración.'], 401);
        }

        Cache::put($llave, true, now()->addMinutes(max(5, (int) config('firma.sesion_minutos'))));

        return $next($request);
    }
}
