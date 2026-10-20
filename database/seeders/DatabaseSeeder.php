<?php

namespace Database\Seeders;

use App\Models\Motivo;
use App\Models\Parametro;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $motivos = [
            'Soy el autor del documento',
            'En señal de conformidad',
            'Doy V° B°',
            'Por encargo',
            'Apruebo el documento',
            'Certifico la autenticidad del documento',
        ];
        foreach ($motivos as $i => $descripcion) {
            // withTrashed: no volver a crear un motivo que fue eliminado lógicamente.
            Motivo::withTrashed()->firstOrCreate(['descripcion' => $descripcion], ['activo' => true, 'orden' => $i]);
        }

        foreach (Parametro::DEFECTOS as $clave => $valor) {
            Parametro::firstOrCreate(['clave' => $clave], ['valor' => $valor]);
        }
    }
}
