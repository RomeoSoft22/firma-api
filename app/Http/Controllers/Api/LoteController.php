<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Agrupa los documentos de una firma masiva. */
class LoteController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            Lote::query()
                ->withCount([
                    'documentos as firmados_count' => fn ($q) => $q->where('estado', 'firmado'),
                    'documentos as errores_count' => fn ($q) => $q->where('estado', 'error'),
                ])
                ->latest()
                ->paginate(15)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'total' => ['required', 'integer', 'min:1'],
        ]);

        return response()->json(Lote::create($datos), 201);
    }

    public function show(Lote $lote): JsonResponse
    {
        return response()->json($lote->load('documentos'));
    }
}
