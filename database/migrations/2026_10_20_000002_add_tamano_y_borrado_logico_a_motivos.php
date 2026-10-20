<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('motivos', function (Blueprint $table) {
            // Tamaño del sello (puntos PDF) para este motivo; si es nulo se usa el de Parámetros de firma.
            $table->unsignedSmallInteger('ancho')->nullable()->after('imagen');
            $table->unsignedSmallInteger('alto')->nullable()->after('ancho');
            // Borrado lógico: el motivo deja de ofrecerse, pero se conserva para auditoría.
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('motivos', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['ancho', 'alto']);
        });
    }
};
