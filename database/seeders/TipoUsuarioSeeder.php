<?php

namespace Database\Seeders;

use App\Models\TipoUsuario;
use Illuminate\Database\Seeder;

class TipoUsuarioSeeder extends Seeder
{
    public function run(): void
    {
        $tipos = ['Administrador', 'Ganadero', 'Veterinario'];

        foreach ($tipos as $tipo) {
            TipoUsuario::firstOrCreate(['nombre' => $tipo]);
        }

        $this->command->info('  ✔ TipoUsuario: Administrador, Ganadero, Veterinario');
    }
}
