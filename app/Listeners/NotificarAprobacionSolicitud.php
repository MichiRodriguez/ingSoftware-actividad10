<?php

namespace App\Listeners;

use App\Events\SolicitudAprobada;
use App\Mail\CredencialesAccesoMail;
use Illuminate\Support\Facades\Mail;

/**
 * PATRÓN OBSERVER — ConcreteObserver
 *
 * Reacciona al evento SolicitudAprobada enviando al solicitante
 * un correo con su correo y contraseña temporal de acceso.
 * El servicio que disparó el evento no conoce este listener (DIP).
 */
class NotificarAprobacionSolicitud
{
    public function handle(SolicitudAprobada $event): void
    {
        Mail::to($event->solicitud->correo)
            ->send(new CredencialesAccesoMail(
                $event->solicitud,
                $event->usuario,
                $event->contrasenaPlana,
            ));
    }
}
