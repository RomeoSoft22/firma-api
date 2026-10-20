<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lote extends Model
{
    protected $fillable = ['nombre', 'total', 'estado'];

    public function documentos(): HasMany
    {
        return $this->hasMany(Documento::class);
    }
}
