<?php

namespace App\Events;

use App\Models\SolicitudRegistro;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * PATRÓN OBSERVER — Subject/Event
 *
 * Se dispara cuando un administrador rechaza una solicitud de registro.
 * Los listeners pueden notificar al solicitante, registrar el rechazo,
 * etc., sin que el servicio sepa de ellos.
 */
class SolicitudRechazada
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly SolicitudRegistro $solicitud,
        public readonly string $motivoRechazo,
    ) {}
}
