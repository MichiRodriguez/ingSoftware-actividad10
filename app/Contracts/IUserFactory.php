<?php

namespace App\Contracts;

use App\Models\User;

/**
 * Factory interface for User creation.
 *
 * Centralizes the instantiation logic so callers depend on this
 * abstraction rather than on concrete constructors. Adding a new
 * user type (e.g., "Técnico") only requires updating the concrete
 * factory, not every point of creation.
 */
interface IUserFactory
{
    /**
     * Build and persist a new User of the given type.
     *
     * @param  string  $tipoNombre  e.g. "Ganadero", "Veterinario", "Administrador"
     * @param  array{nombre: string, correo: string, contrasena: string}  $datos
     */
    public function make(string $tipoNombre, array $datos): User;
}
