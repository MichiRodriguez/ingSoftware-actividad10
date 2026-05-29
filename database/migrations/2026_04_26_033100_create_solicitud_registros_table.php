<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitud_registros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estado_id')->constrained('estado_solicitud');
            $table->string('nombre');
            $table->string('apellidos');
            $table->string('correo');
            $table->string('numero_celular');
            $table->string('archivo_cedula')->nullable(); // ruta en storage
            $table->string('archivo_certificado')->nullable(); // ruta en storage
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitud_registros');
    }
};
