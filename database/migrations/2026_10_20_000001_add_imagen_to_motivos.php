<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('motivos', function (Blueprint $table) {
            // Imagen (rúbrica / logo) que se estampa en el sello cuando se firma con este motivo.
            $table->string('imagen')->nullable()->after('descripcion');
        });
    }

    public function down(): void
    {
        Schema::table('motivos', function (Blueprint $table) {
            $table->dropColumn('imagen');
        });
    }
};
