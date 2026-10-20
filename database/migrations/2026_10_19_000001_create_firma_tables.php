<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lotes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->unsignedInteger('total')->default(0);
            $table->string('estado', 20)->default('en_proceso'); // en_proceso | finalizado
            $table->timestamps();
        });

        Schema::create('documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lote_id')->nullable()->constrained('lotes')->nullOnDelete();
            $table->string('nombre_original');
            $table->string('ruta_original');
            $table->string('hash_original', 64);
            $table->unsignedBigInteger('tamano_original');
            $table->string('nombre_firmado')->nullable();
            $table->string('ruta_firmado')->nullable();
            $table->string('hash_firmado', 64)->nullable();
            $table->string('estado', 20)->default('pendiente'); // pendiente | firmado | error
            $table->text('mensaje_error')->nullable();
            $table->string('firmante')->nullable();
            $table->string('documento_firmante')->nullable();
            $table->string('emisor')->nullable();
            $table->string('numero_serie')->nullable();
            $table->string('motivo')->nullable();
            $table->string('ubicacion')->nullable();
            $table->boolean('sello_tiempo')->default(false);
            $table->timestamp('fecha_firma')->nullable();
            $table->string('equipo')->nullable();
            $table->timestamps();

            $table->index('estado');
            $table->index('hash_firmado');
        });

        Schema::create('motivos', function (Blueprint $table) {
            $table->id();
            $table->string('descripcion');
            $table->boolean('activo')->default(true);
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();
        });

        Schema::create('parametros', function (Blueprint $table) {
            $table->string('clave')->primary();
            $table->text('valor')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parametros');
        Schema::dropIfExists('motivos');
        Schema::dropIfExists('documentos');
        Schema::dropIfExists('lotes');
    }
};
