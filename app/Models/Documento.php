<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Documento extends Model
{
    protected $fillable = [
        'lote_id', 'nombre_original', 'ruta_original', 'hash_original', 'tamano_original',
        'nombre_firmado', 'ruta_firmado', 'hash_firmado', 'estado', 'mensaje_error',
        'firmante', 'documento_firmante', 'emisor', 'numero_serie', 'motivo', 'ubicacion',
        'sello_tiempo', 'fecha_firma', 'equipo',
    ];

    protected $hidden = ['ruta_original', 'ruta_firmado'];

    protected function casts(): array
    {
        return [
            'sello_tiempo' => 'boolean',
            'fecha_firma' => 'datetime',
            'tamano_original' => 'integer',
        ];
    }

    public function lote(): BelongsTo
    {
        return $this->belongsTo(Lote::class);
    }
}
