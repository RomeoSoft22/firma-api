<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Documento;
use App\Models\Lote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Registro de documentos: el PDF original se guarda al subirlo y la versión firmada
 * (producida por el agente local con la clave del usuario) se adjunta después.
 * La API nunca ve ni maneja claves privadas.
 */
class DocumentoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $consulta = Documento::query()->with('lote:id,nombre')->latest();

        if ($estado = $request->query('estado')) {
            $consulta->where('estado', $estado);
        }
        if ($lote = $request->query('lote_id')) {
            $consulta->where('lote_id', $lote);
        }
        if ($texto = $request->query('q')) {
            $consulta->where(function ($q) use ($texto) {
                $q->where('nombre_original', 'like', "%{$texto}%")
                    ->orWhere('firmante', 'like', "%{$texto}%")
                    ->orWhere('documento_firmante', 'like', "%{$texto}%");
            });
        }

        return response()->json($consulta->paginate((int) $request->query('por_pagina', 15)));
    }

    public function store(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'archivo' => ['required', 'file', 'mimes:pdf', 'max:204800'],
            'lote_id' => ['nullable', 'exists:lotes,id'],
        ]);

        $archivo = $request->file('archivo');
        $ruta = $archivo->store('documentos/originales');

        $documento = Documento::create([
            'lote_id' => $datos['lote_id'] ?? null,
            'nombre_original' => $archivo->getClientOriginalName(),
            'ruta_original' => $ruta,
            'hash_original' => hash_file('sha256', $archivo->getRealPath()),
            'tamano_original' => $archivo->getSize(),
            'estado' => 'pendiente',
        ]);

        // Token de carga: solo quien subió el original puede adjuntar el PDF firmado o el error.
        $token = bin2hex(random_bytes(24));
        Cache::put($this->llaveCarga($documento), hash('sha256', $token), now()->addHours(12));

        return response()->json([...$documento->toArray(), 'token_carga' => $token], 201);
    }

    private function llaveCarga(Documento $documento): string
    {
        return 'carga-documento:'.$documento->id;
    }

    /** Valida el token de carga (cabecera X-Token-Carga) emitido al subir el original. */
    private function exigirTokenCarga(Request $request, Documento $documento): void
    {
        $esperado = Cache::get($this->llaveCarga($documento));
        $recibido = hash('sha256', (string) $request->header('X-Token-Carga'));
        abort_unless(is_string($esperado) && hash_equals($esperado, $recibido), 403);
    }

    public function show(Documento $documento): JsonResponse
    {
        return response()->json($documento->load('lote:id,nombre'));
    }

    /** Recibe el PDF ya firmado por el agente local y los datos del firmante. */
    public function registrarFirma(Request $request, Documento $documento): JsonResponse
    {
        $this->exigirTokenCarga($request, $documento);
        $datos = $request->validate([
            'archivo' => ['required', 'file', 'mimes:pdf', 'max:204800'],
            'firmante' => ['required', 'string', 'max:255'],
            'documento_firmante' => ['nullable', 'string', 'max:255'],
            'emisor' => ['nullable', 'string', 'max:255'],
            'numero_serie' => ['nullable', 'string', 'max:255'],
            'motivo' => ['nullable', 'string', 'max:255'],
            'ubicacion' => ['nullable', 'string', 'max:255'],
            'sello_tiempo' => ['nullable', 'boolean'],
            'fecha_firma' => ['nullable', 'date'],
            'equipo' => ['nullable', 'string', 'max:255'],
        ]);

        $archivo = $request->file('archivo');
        if ($documento->ruta_firmado) {
            Storage::delete($documento->ruta_firmado);
        }

        $documento->fill(collect($datos)->except(['archivo', 'fecha_firma'])->all());
        $documento->fill([
            'nombre_firmado' => $archivo->getClientOriginalName(),
            'ruta_firmado' => $archivo->store('documentos/firmados'),
            'hash_firmado' => hash_file('sha256', $archivo->getRealPath()),
            'estado' => 'firmado',
            'mensaje_error' => null,
            // El agente envía hora local con offset (p. ej. -05:00); se guarda normalizada a UTC.
            'fecha_firma' => isset($datos['fecha_firma']) ? Carbon::parse($datos['fecha_firma'])->utc() : now(),
        ])->save();

        Cache::forget($this->llaveCarga($documento)); // el token es de un solo uso
        $this->cerrarLoteSiCorresponde($documento);

        return response()->json($documento);
    }

    public function registrarError(Request $request, Documento $documento): JsonResponse
    {
        $this->exigirTokenCarga($request, $documento);
        $datos = $request->validate(['mensaje' => ['required', 'string', 'max:2000']]);
        $documento->update(['estado' => 'error', 'mensaje_error' => $datos['mensaje']]);
        $this->cerrarLoteSiCorresponde($documento);

        return response()->json($documento);
    }

    public function descargar(Documento $documento, string $tipo): StreamedResponse
    {
        abort_unless(in_array($tipo, ['original', 'firmado'], true), 404);
        $ruta = $tipo === 'firmado' ? $documento->ruta_firmado : $documento->ruta_original;
        $nombre = $tipo === 'firmado' ? $documento->nombre_firmado : $documento->nombre_original;
        abort_unless($ruta && Storage::exists($ruta), 404, 'Archivo no disponible.');

        return Storage::download($ruta, $nombre, ['Content-Type' => 'application/pdf']);
    }

    /** Busca un documento emitido por este sistema a partir del hash SHA-256 del PDF firmado. */
    public function buscarPorHash(string $hash): JsonResponse
    {
        $documento = Documento::where('hash_firmado', strtolower($hash))->first();

        // Ruta pública (pantalla Verificar): solo los datos necesarios para confirmar la emisión.
        return $documento
            ? response()->json($documento->only(['id', 'nombre_original', 'firmante', 'fecha_firma', 'estado']))
            : response()->json(['message' => 'No registrado en este sistema.'], 404);
    }

    public function destroy(Documento $documento): JsonResponse
    {
        Storage::delete(array_filter([$documento->ruta_original, $documento->ruta_firmado]));
        $documento->delete();

        return response()->json(null, 204);
    }

    public function estadisticas(): JsonResponse
    {
        // "Hoy" según la zona horaria local de la institución (las fechas se guardan en UTC).
        $zona = env('APP_ZONA_LOCAL', 'America/Lima');
        $inicio = now($zona)->startOfDay()->utc();
        $fin = now($zona)->endOfDay()->utc();

        return response()->json([
            'total' => Documento::count(),
            'firmados' => Documento::where('estado', 'firmado')->count(),
            'pendientes' => Documento::where('estado', 'pendiente')->count(),
            'errores' => Documento::where('estado', 'error')->count(),
            'hoy' => Documento::where('estado', 'firmado')->whereBetween('fecha_firma', [$inicio, $fin])->count(),
        ]);
    }

    private function cerrarLoteSiCorresponde(Documento $documento): void
    {
        if (! $documento->lote_id) {
            return;
        }
        $lote = Lote::find($documento->lote_id);
        if ($lote && $lote->documentos()->where('estado', 'pendiente')->doesntExist()
            && $lote->documentos()->count() >= $lote->total) {
            $lote->update(['estado' => 'finalizado']);
        }
    }
}
