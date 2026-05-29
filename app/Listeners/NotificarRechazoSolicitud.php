<?php

namespace App\Listeners;

use App\Events\SolicitudRechazada;
use App\Mail\RechazoSolicitudMail;
use Illuminate\Support\Facades\Mail;

/**
 * PATRÓN OBSERVER — ConcreteObserver
 *
 * Reacciona al evento SolicitudRechazada notificando al solicitante
 * el motivo del rechazo. Agregar más observers (SMS, webhook) no
 * requiere modificar ni el evento ni este listener (OCP).
 */
class NotificarRechazoSolicitud
{
    public function handle(SolicitudRechazada $event): void
    {
        Mail::to($event->solicitud->correo)
            ->send(new RechazoSolicitudMail(
                $event->solicitud,
                $event->motivoRechazo,
            ));
    }
}
