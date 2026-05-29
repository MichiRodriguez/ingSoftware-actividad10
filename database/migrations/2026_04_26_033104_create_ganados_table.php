<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ganados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finca_id')->constrained('fincas');
            $table->foreignId('estado_salud_id')->constrained('estado_salud_ganados');
            $table->foreignId('estado_comercial_id')->constrained('estado_comercial_ganados');
            $table->string('arete')->unique();
            $table->enum('sexo', ['Macho', 'Hembra']);
            $table->string('raza');
            $table->string('imagen'); // ruta en storage
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ganados');
    }
};