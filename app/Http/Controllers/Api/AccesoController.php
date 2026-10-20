<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Middleware\RequiereAcceso;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Acceso de administración (Historial y Configuración) con la clave de ADMIN_CLAVE en .env.
 * Limita los intentos fallidos por IP y globalmente para impedir ataques de fuerza bruta.
 */
class AccesoController extends Controller
{
    private const LLAVE_GLOBAL = 'acceso-admin:global';

    public function entrar(Request $request): JsonResponse
    {
        $clave = (string) config('firma.clave_admin');
        if ($clave === '') {
            return response()->json(['message' => 'El acceso de administración no está configurado en el servidor.'], 503);
        }

        $datos = $request->validate(['clave' => ['required', 'string', 'max:200']]);

        $llaveIp = 'acceso-admin:ip:'.sha1((string) $request->ip());
        $intentos = max(1, (int) config('firma.intentos'));
        $bloqueo = max(1, (int) config('firma.bloqueo_minutos')) * 60;
        $globales = max($intentos, (int) config('firma.intentos_globales'));

        if (RateLimiter::tooManyAttempts($llaveIp, $intentos) || RateLimiter::tooManyAttempts(self::LLAVE_GLOBAL, $globales)) {
            $segundos = max(RateLimiter::availableIn($llaveIp), RateLimiter::availableIn(self::LLAVE_GLOBAL));
            $minutos = max(1, (int) ceil($segundos / 60));

            return response()->json(
                ['message' => "Demasiados intentos fallidos. Intente nuevamente en {$minutos} minuto(s)."],
                429,
                ['Retry-After' => $segundos],
            );
        }

        if (! hash_equals($clave, $datos['clave'])) {
            RateLimiter::hit($llaveIp, $bloqueo);
            RateLimiter::hit(self::LLAVE_GLOBAL, $bloqueo);
            usleep(random_int(300_000, 700_000)); // ralentiza los intentos automatizados
            $restantes = RateLimiter::remaining($llaveIp, $intentos);

            return response()->json([
                'message' => $restantes > 0
                    ? "Clave incorrecta. Le quedan {$restantes} intento(s)."
                    : "Clave incorrecta. Acceso bloqueado durante {$this->minutos($bloqueo)} minuto(s).",
                'intentos_restantes' => $restantes,
            ], 401);
        }

        RateLimiter::clear($llaveIp);
        $token = bin2hex(random_bytes(32));
        $minutosSesion = max(5, (int) config('firma.sesion_minutos'));
        Cache::put(RequiereAcceso::llave($token), true, now()->addMinutes($minutosSesion));

        return response()->json(['token' => $token, 'expira_en' => $minutosSesion * 60]);
    }

    /** Comprueba que la sesión siga vigente (la ruta está protegida por RequiereAcceso). */
    public function verificar(): JsonResponse
    {
        return response()->json(['acceso' => true]);
    }

    public function salir(Request $request): JsonResponse
    {
        if ($token = $request->bearerToken()) {
            Cache::forget(RequiereAcceso::llave($token));
        }

        return response()->json(null, 204);
    }

    private function minutos(int $segundos): int
    {
        return (int) ceil($segundos / 60);
    }
}
