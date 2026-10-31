<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Logos de la marca configurables desde Configuración (web y aplicación de escritorio).
 * Variantes: símbolo y logo horizontal, cada uno para fondo claro (logo oscuro) y para fondo oscuro
 * (logo blanco). Si no se sube ninguno, el front usa los incluidos en /marca.
 */
class MarcaController extends Controller
{
    public const VARIANTES = ['simbolo-claro', 'simbolo-oscuro', 'logo-claro', 'logo-oscuro'];

    private const CARPETA = 'marca';

    /** Qué variantes están personalizadas y una versión para invalidar la caché del navegador. */
    public function index(): JsonResponse
    {
        $personalizados = [];
        $version = 0;
        foreach (self::VARIANTES as $variante) {
            if ($ruta = $this->ruta($variante)) {
                $personalizados[$variante] = true;
                $version = max($version, Storage::lastModified($ruta));
            }
        }

        return response()->json(['version' => $version, 'personalizados' => (object) $personalizados]);
    }

    public function mostrar(string $variante): BinaryFileResponse
    {
        $ruta = $this->ruta($variante);
        abort_unless($ruta, 404);

        return response()->file(Storage::path($ruta), [
            // La URL lleva ?v=<versión>: se puede guardar en caché sin quedar desactualizada.
            'Cache-Control' => 'public, max-age=604800',
        ]);
    }

    public function subir(Request $request, string $variante): JsonResponse
    {
        $request->validate([
            'imagen' => ['required', 'file', 'mimes:png,jpg,jpeg,webp', 'max:2048', 'dimensions:max_width=4000,max_height=4000'],
        ]);

        $this->borrar($variante);
        $archivo = $request->file('imagen');
        $archivo->storeAs(self::CARPETA, $variante.'.'.strtolower($archivo->extension()));

        return $this->index();
    }

    public function restaurar(string $variante): JsonResponse
    {
        $this->borrar($variante);

        return $this->index();
    }

    private function ruta(string $variante): ?string
    {
        foreach (['png', 'jpg', 'jpeg', 'webp'] as $ext) {
            $ruta = self::CARPETA."/{$variante}.{$ext}";
            if (Storage::exists($ruta)) {
                return $ruta;
            }
        }

        return null;
    }

    private function borrar(string $variante): void
    {
        while ($ruta = $this->ruta($variante)) {
            Storage::delete($ruta);
        }
    }
}
