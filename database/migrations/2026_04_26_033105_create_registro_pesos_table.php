<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('registro_pesos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ganado_id')->constrained('ganados');
            $table->decimal('peso_estimado', 8, 2);
            $table->decimal('peso_corregido', 8, 2)->nullable();
            $table->date('fecha');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registro_pesos');
    }
};