<?php

namespace Database\Seeders;

use App\Models\TipoUsuario;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $usuarios = [
            [
                'tipo_nombre' => 'Administrador',
                'nombre' => 'Admin BovWeight',
                'correo' => 'admin@bovweight.com',
                'contrasena' => 'password123',
            ],
            [
                'tipo_nombre' => 'Ganadero',
                'nombre' => 'Carlos Ganadero',
                'correo' => 'ganadero@bovweight.com',
                'contrasena' => 'password123',
            ],
            [
                'tipo_nombre' => 'Veterinario',
                'nombre' => 'Dra. Ana Veterinaria',
                'correo' => 'veterinario@bovweight.com',
                'contrasena' => 'password123',
            ],
        ];

        foreach ($usuarios as $datos) {
            $tipo = TipoUsuario::where('nombre', $datos['tipo_nombre'])->firstOrFail();

            User::updateOrCreate(
                ['correo' => $datos['correo']],
                [
                    'tipo_id' => $tipo->id,
                    'nombre' => $datos['nombre'],
                    'contrasena' => Hash::make($datos['contrasena']),
                ]
            );
        }

        $this->command->info('  ✔ Usuarios de prueba creados:');
        $this->command->info('      admin@bovweight.com        / password123  (Administrador)');
        $this->command->info('      ganadero@bovweight.com     / password123  (Ganadero)');
        $this->command->info('      veterinario@bovweight.com  / password123  (Veterinario)');
    }
}
