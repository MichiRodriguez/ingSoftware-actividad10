<?php

namespace App\Events;

use App\Models\SolicitudRegistro;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * PATRÓN OBSERVER — Subject/Event
 *
 * Se dispara cuando un administrador aprueba una solicitud de registro
 * y el usuario correspondiente ha sido creado en el sistema.
 * Cualquier Listener suscrito reacciona sin que el servicio conozca
 * quiénes son ni cuántos hay (OCP garantizado).
 */
class SolicitudAprobada
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly SolicitudRegistro $solicitud,
        public readonly User $usuario,
        public readonly string $contrasenaPlana,
    ) {}
}
