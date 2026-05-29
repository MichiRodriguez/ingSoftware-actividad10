<?php

namespace Database\Seeders;

use App\Models\EstadoComercialGanado;
use Illuminate\Database\Seeder;

class EstadoComercialGanadoSeeder extends Seeder
{
    public function run(): void
    {
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        EstadoComercialGanado::truncate();
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $estados = ['Disponible', 'Reservado', 'En negociación', 'Vendido', 'No disponible'];

        foreach ($estados as $estado) {
            EstadoComercialGanado::create(['nombre' => $estado]);
        }

        $this->command->info('  ✔ EstadoComercialGanado: Disponible, Reservado, En negociación, Vendido, No disponible');
    }
}
