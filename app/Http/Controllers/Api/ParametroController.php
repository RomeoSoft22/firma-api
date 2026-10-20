<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Parametro;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ParametroController extends Controller
{
    /** Regla de validación de cada parámetro según su tipo. */
    private const REGLAS = [
        'tsa_url' => ['nullable', 'url:http,https', 'max:500'],
        'usar_sello_tiempo' => ['nullable', 'in:0,1'],
        'incluir_revocacion' => ['nullable', 'in:0,1'],
        'firma_visible' => ['nullable', 'in:0,1'],
        'ubicacion' => ['nullable', 'string', 'max:255'],
        'motivo_defecto' => ['nullable', 'string', 'max:255'],
        'sufijo_firmado' => ['nullable', 'string', 'max:50', 'regex:/^[^\\\\\/:*?"<>|]*$/'],
        'firma_ancho' => ['nullable', 'integer', 'between:60,600'],
        'firma_alto' => ['nullable', 'integer', 'between:30,300'],
    ];

    public function index(): JsonResponse
    {
        return response()->json(Parametro::todos());
    }

    public function update(Request $request): JsonResponse
    {
        $datos = $request->validate(self::REGLAS);

        foreach ($datos as $clave => $valor) {
            // Todos los valores se guardan como texto (la tabla es clave/valor).
            Parametro::updateOrCreate(['clave' => $clave], ['valor' => $valor === null ? null : (string) $valor]);
        }

        return response()->json(Parametro::todos());
    }
}
