<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * PATRÓN OBSERVER — Subject/Event
 *
 * Se dispara cuando el administrador crea un usuario directamente
 * (HU-01.4). Permite enviar credenciales de bienvenida u otros
 * efectos secundarios sin acoplarlos al servicio creador.
 */
class UsuarioCreado
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly User $usuario,
        public readonly string $contrasenaPlana,
    ) {}
}
