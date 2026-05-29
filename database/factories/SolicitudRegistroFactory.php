<?php

namespace Database\Factories;

use App\Models\EstadoSolicitud;
use App\Models\SolicitudRegistro;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SolicitudRegistro>
 */
class SolicitudRegistroFactory extends Factory
{
    protected $model = SolicitudRegistro::class;

    public function definition(): array
    {
        return [
            'estado_id' => EstadoSolicitud::where('nombre', 'Pendiente')->first()?->id ?? 1,
            'nombre' => fake()->firstName(),
            'apellidos' => fake()->lastName().' '.fake()->lastName(),
            'correo' => fake()->unique()->safeEmail(),
            'numero_celular' => fake()->numerify('########'),
            'archivo_cedula' => null,
            'archivo_certificado' => null,
            'motivo_rechazo' => null,
        ];
    }

    /** Estado: solicitud pendiente de revisión. */
    public function pendiente(): static
    {
        return $this->state(fn () => [
            'estado_id' => EstadoSolicitud::where('nombre', 'Pendiente')->first()?->id ?? 1,
        ]);
    }

    /** Estado: solicitud aprobada. */
    public function aprobada(): static
    {
        return $this->state(fn () => [
            'estado_id' => EstadoSolicitud::where('nombre', 'Aprobado')->first()?->id ?? 2,
        ]);
    }

    /** Estado: solicitud rechazada. */
    public function rechazada(): static
    {
        return $this->state(fn () => [
            'estado_id' => EstadoSolicitud::where('nombre', 'Rechazado')->first()?->id ?? 3,
            'motivo_rechazo' => fake()->sentence(),
        ]);
    }
}
