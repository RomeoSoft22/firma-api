<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('motivos', function (Blueprint $table) {
            // Diseño del sello: 'horizontal' = imagen a la izquierda y texto a la derecha;
            // 'vertical' = imagen arriba y texto abajo.
            $table->string('disposicion', 20)->default('horizontal')->after('alto');
        });

        // El visto bueno usa el ícono arriba y el texto abajo.
        DB::table('motivos')->where('descripcion', 'like', '%V° B°%')->update(['disposicion' => 'vertical']);
    }

    public function down(): void
    {
        Schema::table('motivos', function (Blueprint $table) {
            $table->dropColumn('disposicion');
        });
    }
};
