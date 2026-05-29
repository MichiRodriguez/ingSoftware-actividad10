<?php

namespace Database\Seeders;

use App\Models\EstadoSaludGanado;
use Illuminate\Database\Seeder;

class EstadoSaludGanadoSeeder extends Seeder
{
    public function run(): void
    {
        $estados = ['Sano', 'En tratamiento', 'Crítico'];

        foreach ($estados as $estado) {
            EstadoSaludGanado::firstOrCreate(['nombre' => $estado]);
        }

        $this->command->info('  ✔ EstadoSaludGanado: Sano, En tratamiento, Crítico');
    }
}
