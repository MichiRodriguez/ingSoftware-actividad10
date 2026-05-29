<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fincas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('users');
            $table->foreignId('veterinario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nombre');
            $table->string('ubicacion');
            $table->decimal('area', 10, 2);
            $table->string('numero_finca');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fincas');
    }
};
