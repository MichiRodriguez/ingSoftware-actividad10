<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            TipoUsuarioSeeder::class,
            EstadoSolicitudSeeder::class,
            EstadoSaludGanadoSeeder::class,
            EstadoComercialGanadoSeeder::class,
            UserSeeder::class,
            SolicitudRegistroSeeder::class,
        ]);
    }
}
