<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registro_pesos', function (Blueprint $table) {
            $table->float('confianza')->nullable()->after('peso_corregido');
            $table->string('metodo')->nullable()->after('confianza');
            $table->string('imagen_path')->nullable()->after('metodo');
            $table->json('medidas')->nullable()->after('imagen_path');
            $table->string('raza_estimacion')->default('default')->after('medidas');
        });
    }

    public function down(): void
    {
        Schema::table('registro_pesos', function (Blueprint $table) {
            $table->dropColumn(['confianza', 'metodo', 'imagen_path', 'medidas', 'raza_estimacion']);
        });
    }
};