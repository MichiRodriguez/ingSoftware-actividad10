<?php

namespace App\Factories;

use App\Contracts\IUserFactory;
use App\Models\TipoUsuario;
use App\Models\User;
use InvalidArgumentException;

/**
 * PATRÓN FACTORY — ConcreteCreator
 *
 * Centraliza la creación de usuarios por tipo.  Cualquier controlador
 * o servicio que necesite un User nuevo depende de IUserFactory, no de
 * constructores concretos.  Agregar el tipo "Técnico" solo requiere
 * registrarlo en la base de datos; esta clase no cambia.
 *
 * Participantes GoF:
 *   Creator (interfaz) : IUserFactory
 *   ConcreteCreator    : UserFactory  ← esta clase
 *   Product            : User (modelo Eloquent)
 */
class UserFactory implements IUserFactory
{
    /**
     * Mapa tipo_nombre → defaults adicionales.
     * Permite personalizar atributos por tipo sin if/else en el exterior.
     *
     * @var array<string, array<string, mixed>>
     */
    private array $defaults = [
        'Administrador' => [],
        'Ganadero' => [],
        'Veterinario' => [],
    ];

    /**
     * Construye y persiste un nuevo User del tipo indicado.
     *
     * @param  string  $tipoNombre  Nombre del tipo ("Ganadero", "Veterinario", "Administrador")
     * @param  array{nombre: string, correo: string, contrasena: string}  $datos
     *
     * @throws InvalidArgumentException cuando el tipo no existe en la base de datos.
     */
    public function make(string $tipoNombre, array $datos): User
    {
        $tipo = TipoUsuario::where('nombre', $tipoNombre)->first();

        if (! $tipo) {
            throw new InvalidArgumentException("Tipo de usuario desconocido: {$tipoNombre}");
        }

        $atributos = array_merge(
            $this->defaults[$tipoNombre] ?? [],
            $datos,
            ['tipo_id' => $tipo->id]
        );

        return User::create($atributos);
    }
}
