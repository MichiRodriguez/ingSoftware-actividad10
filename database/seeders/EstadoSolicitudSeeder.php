<?php

namespace Database\Seeders;

use App\Models\EstadoSolicitud;
use Illuminate\Database\Seeder;

class EstadoSolicitudSeeder extends Seeder
{
    public function run(): void
    {
        $estados = ['Pendiente', 'Aprobado', 'Rechazado'];

        foreach ($estados as $estado) {
            EstadoSolicitud::firstOrCreate(['nombre' => $estado]);
        }

        $this->command->info('  ✔ EstadoSolicitud: Pendiente, Aprobado, Rechazado');
    }
}
