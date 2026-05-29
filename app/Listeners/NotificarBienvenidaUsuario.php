<?php

namespace App\Listeners;

use App\Events\UsuarioCreado;
use App\Mail\BienvenidaUsuarioMail;
use Illuminate\Support\Facades\Mail;

/**
 * PATRÓN OBSERVER — ConcreteObserver
 *
 * Reacciona al evento UsuarioCreado (creación directa por admin, HU-01.4)
 * enviando las credenciales de bienvenida al nuevo usuario.
 */
class NotificarBienvenidaUsuario
{
    public function handle(UsuarioCreado $event): void
    {
        Mail::to($event->usuario->correo)
            ->send(new BienvenidaUsuarioMail(
                $event->usuario,
                $event->contrasenaPlana,
            ));
    }
}
