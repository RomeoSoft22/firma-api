<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Equivalente a ServiceParametro / Connection (XML) de la app de escritorio. */
class Parametro extends Model
{
    protected $primaryKey = 'clave';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['clave', 'valor'];

    /** Claves editables desde el front, con su valor por defecto. */
    public const DEFECTOS = [
        'tsa_url' => 'http://timestamp.digicert.com',
        'usar_sello_tiempo' => '0',
        'incluir_revocacion' => '0',
        'ubicacion' => 'Lima, Perú',
        'motivo_defecto' => 'Soy el autor del documento',
        'firma_visible' => '1',
        'sufijo_firmado' => '[FIRMADO]',
        'firma_ancho' => '200',
        'firma_alto' => '60',
    ];

    public static function todos(): array
    {
        $guardados = static::query()->pluck('valor', 'clave')->all();
        return array_merge(self::DEFECTOS, array_intersect_key($guardados, self::DEFECTOS));
    }
}
