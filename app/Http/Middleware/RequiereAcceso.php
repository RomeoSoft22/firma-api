<?php

namespace App\Http\Middleware;

use App\Support\Identidad;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Historial y Configuración: exige el JWT que emite identidad.juarezcampos.net para FIRMA
 * (Authorization: Bearer), vigente y con un rol de administración. Los claims quedan en
 * $request->attributes->get('identidad') para auditar quién hizo cada acción.
 */
class RequiereAcceso
{
    public function handle(Request $request, Closure $next): Response
    {
        $jwt = (string) $request->bearerToken();
        if ($jwt === '') {
            return response()->json(['codigo' => 'no_autenticado', 'message' => 'Inicia sesión para continuar.'], 401);
        }

        try {
            $claims = Identidad::verificar($jwt);
        } catch (Throwable) {
            return response()->json(['codigo' => 'no_autenticado', 'message' => 'Tu sesión no es válida o venció. Vuelve a iniciar sesión.'], 401);
        }

        if (! Identidad::esAdministrador($claims)) {
            return response()->json(['codigo' => 'sin_permiso', 'message' => 'Tu cuenta no tiene permiso para Historial ni Configuración.'], 403);
        }

        $request->attributes->set('identidad', $claims);

        return $next($request);
    }
}
