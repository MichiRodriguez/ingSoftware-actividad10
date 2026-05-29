<?php

namespace Database\Factories;

use App\Models\TipoUsuario;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'tipo_id' => TipoUsuario::where('nombre', 'Ganadero')->first()?->id ?? 2,
            'nombre' => fake()->name(),
            'correo' => fake()->unique()->safeEmail(),
            'contrasena' => 'password', // El cast 'hashed' en el modelo lo cifra automáticamente
            'remember_token' => Str::random(10),
        ];
    }

    /** Estado: usuario de tipo Administrador. */
    public function administrador(): static
    {
        return $this->state(fn () => [
            'tipo_id' => TipoUsuario::where('nombre', 'Administrador')->first()?->id ?? 1,
        ]);
    }

    /** Estado: usuario de tipo Ganadero. */
    public function ganadero(): static
    {
        return $this->state(fn () => [
            'tipo_id' => TipoUsuario::where('nombre', 'Ganadero')->first()?->id ?? 2,
        ]);
    }

    /** Estado: usuario de tipo Veterinario. */
    public function veterinario(): static
    {
        return $this->state(fn () => [
            'tipo_id' => TipoUsuario::where('nombre', 'Veterinario')->first()?->id ?? 3,
        ]);
    }
}
