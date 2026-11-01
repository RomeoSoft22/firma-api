<?php

namespace App\Support;

use App\Models\Parametro;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Acceso de administración desde la aplicación de escritorio Firma Digital con una sola contraseña
 * («Opciones de administración»). La contraseña la define un administrador con identidad desde la web
 * (Configuración → Clave de administración) y se guarda solo como hash. Al acertarla se emite un token
 * opaco de vida corta que RequiereAcceso acepta igual que un JWT de identidad con rol ADMIN.
 */
class AccesoEscritorio
{
    /** Prefijo que distingue estos tokens de los JWT de identidad. */
    public const PREFIJO = 'fdc_';

    public const MINUTOS = 60;

    private const PARAMETRO = 'clave_escritorio';

    /** Claims con los que se audita lo que se hace con la clave de escritorio. */
    public const CLAIMS = ['sub' => 'escritorio', 'name' => 'Administrador (aplicación de escritorio)', 'email' => null, 'roles' => ['ADMIN']];

    public static function definida(): bool
    {
        return (bool) Parametro::query()->whereKey(self::PARAMETRO)->value('valor');
    }

    public static function definir(string $clave): void
    {
        Parametro::updateOrCreate(['clave' => self::PARAMETRO], ['valor' => Hash::make($clave)]);
    }

    public static function correcta(string $clave): bool
    {
        $hash = Parametro::query()->whereKey(self::PARAMETRO)->value('valor');

        return $hash !== null && Hash::check($clave, $hash);
    }

    /** Emite un token nuevo; en caché solo queda su SHA-256. */
    public static function emitir(): string
    {
        $token = self::PREFIJO.Str::random(64);
        Cache::put(self::llave($token), true, now()->addMinutes(self::MINUTOS));

        return $token;
    }

    public static function valido(string $token): bool
    {
        return str_starts_with($token, self::PREFIJO) && Cache::has(self::llave($token));
    }

    public static function revocar(string $token): void
    {
        Cache::forget(self::llave($token));
    }

    private static function llave(string $token): string
    {
        return 'firma:escritorio:'.hash('sha256', $token);
    }
}
