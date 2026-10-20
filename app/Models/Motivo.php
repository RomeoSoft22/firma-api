<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Motivo extends Model
{
    use SoftDeletes;

    protected $fillable = ['descripcion', 'activo', 'orden', 'ancho', 'alto', 'disposicion'];

    /** La ruta interna del archivo no se expone; el front usa GET /motivos/{id}/imagen. */
    protected $hidden = ['imagen', 'deleted_at'];

    protected $appends = ['tiene_imagen'];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'orden' => 'integer',
            'ancho' => 'integer',
            'alto' => 'integer',
        ];
    }

    public function getTieneImagenAttribute(): bool
    {
        return ! empty($this->imagen);
    }
}
