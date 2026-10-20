<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Motivo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MotivoController extends Controller
{
    private const SIN_MOTIVOS = 'Debe existir al menos un motivo activo: no se puede eliminar ni desactivar el último.';

    /** Todos los motivos vigentes, activos e inactivos (Configuración; requiere acceso). */
    public function index(): JsonResponse
    {
        return response()->json(Motivo::query()->orderBy('orden')->orderBy('descripcion')->get());
    }

    /** Motivos activos para la pantalla de firma (público, solo lectura). */
    public function activos(): JsonResponse
    {
        return response()->json(
            Motivo::query()->where('activo', true)->orderBy('orden')->orderBy('descripcion')->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json(Motivo::create($this->validar($request)), 201);
    }

    public function update(Request $request, Motivo $motivo): JsonResponse
    {
        $datos = $this->validar($request);

        DB::transaction(function () use ($motivo, $datos) {
            if ($motivo->activo && array_key_exists('activo', $datos) && ! $datos['activo']) {
                $this->exigirOtroMotivoActivo($motivo);
            }
            $motivo->update($datos);
        });

        return response()->json($motivo);
    }

    /** Borrado lógico: el motivo deja de ofrecerse, pero se conserva (y su imagen) para auditoría. */
    public function destroy(Motivo $motivo): JsonResponse
    {
        DB::transaction(function () use ($motivo) {
            $this->exigirOtroMotivoActivo($motivo);
            $motivo->delete();
        });

        return response()->json(null, 204);
    }

    /** Imagen del sello para este motivo (PNG o JPG, máx. 1 MB). */
    public function subirImagen(Request $request, Motivo $motivo): JsonResponse
    {
        $request->validate([
            'imagen' => ['required', 'file', 'mimes:png,jpg,jpeg', 'max:1024', 'dimensions:max_width=2000,max_height=2000'],
        ]);

        if ($motivo->imagen) {
            Storage::delete($motivo->imagen);
        }
        $motivo->imagen = $request->file('imagen')->store('motivos');
        $motivo->save();

        return response()->json($motivo);
    }

    public function imagen(Motivo $motivo): BinaryFileResponse
    {
        abort_unless($motivo->imagen && Storage::exists($motivo->imagen), 404);

        return response()->file(Storage::path($motivo->imagen), [
            'Cache-Control' => 'no-cache',
        ]);
    }

    public function eliminarImagen(Motivo $motivo): JsonResponse
    {
        if ($motivo->imagen) {
            Storage::delete($motivo->imagen);
            $motivo->imagen = null;
            $motivo->save();
        }

        return response()->json($motivo);
    }

    /** Impide que el sistema se quede sin motivos activos. */
    private function exigirOtroMotivoActivo(Motivo $motivo): void
    {
        $otrosActivos = Motivo::query()
            ->where('activo', true)
            ->whereKeyNot($motivo->getKey())
            ->lockForUpdate()
            ->count();

        if ($otrosActivos === 0) {
            throw ValidationException::withMessages(['motivo' => self::SIN_MOTIVOS]);
        }
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'descripcion' => ['required', 'string', 'max:255'],
            'activo' => ['boolean'],
            'orden' => ['integer', 'min:0'],
            // Tamaño del sello en puntos PDF; vacío = usar el de Parámetros de firma.
            'ancho' => ['nullable', 'integer', 'between:60,600'],
            'alto' => ['nullable', 'integer', 'between:30,300'],
            // Diseño del sello: imagen a la izquierda (horizontal) o arriba (vertical).
            'disposicion' => ['sometimes', 'in:horizontal,vertical'],
        ]);
    }
}
