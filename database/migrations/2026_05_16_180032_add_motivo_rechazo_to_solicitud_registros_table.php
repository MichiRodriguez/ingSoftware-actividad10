<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('solicitud_registros', function (Blueprint $table) {
            $table->text('motivo_rechazo')->nullable()->after('archivo_certificado');
        });
    }

    public function down(): void
    {
        Schema::table('solicitud_registros', function (Blueprint $table) {
            $table->dropColumn('motivo_rechazo');
        });
    }
};
