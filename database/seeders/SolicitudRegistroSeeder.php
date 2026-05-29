<?php

namespace Database\Seeders;

use App\Models\EstadoSolicitud;
use App\Models\SolicitudRegistro;
use Illuminate\Database\Seeder;

class SolicitudRegistroSeeder extends Seeder
{
    public function run(): void
    {
        $pendiente = EstadoSolicitud::where('nombre', 'Pendiente')->firstOrFail();
        $aprobado = EstadoSolicitud::where('nombre', 'Aprobado')->firstOrFail();
        $rechazado = EstadoSolicitud::where('nombre', 'Rechazado')->firstOrFail();

        // Solicitudes pendientes
        $pendientes = [
            [
                'nombre' => 'Luis',
                'apellidos' => 'Fernández Mora',
                'correo' => 'luis.fernandez@correo.com',
                'numero_celular' => '88112233',
            ],
            [
                'nombre' => 'Marta',
                'apellidos' => 'Rodríguez Soto',
                'correo' => 'marta.rodriguez@correo.com',
                'numero_celular' => '87654321',
            ],
        ];

        foreach ($pendientes as $datos) {
            SolicitudRegistro::firstOrCreate(
                ['correo' => $datos['correo']],
                array_merge($datos, ['estado_id' => $pendiente->id])
            );
        }

        // Solicitud aprobada
        SolicitudRegistro::firstOrCreate(
            ['correo' => 'pedro.aprobado@correo.com'],
            [
                'nombre' => 'Pedro',
                'apellidos' => 'Vargas Jiménez',
                'correo' => 'pedro.aprobado@correo.com',
                'numero_celular' => '86001122',
                'estado_id' => $aprobado->id,
            ]
        );

        // Solicitud rechazada
        SolicitudRegistro::firstOrCreate(
            ['correo' => 'ana.rechazada@correo.com'],
            [
                'nombre' => 'Ana',
                'apellidos' => 'Méndez Castro',
                'correo' => 'ana.rechazada@correo.com',
                'numero_celular' => '85009988',
                'estado_id' => $rechazado->id,
                'motivo_rechazo' => 'Documentación incompleta. Por favor adjunte su certificado veterinario.',
            ]
        );

        $this->command->info('  ✔ SolicitudRegistro: 2 pendientes, 1 aprobada, 1 rechazada');
    }
}
