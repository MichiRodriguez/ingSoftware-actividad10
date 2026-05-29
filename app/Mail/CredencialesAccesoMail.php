<?php

namespace App\Mail;

use App\Models\SolicitudRegistro;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Correo enviado al solicitante cuando el administrador aprueba su solicitud.
 * Incluye el correo y la contraseña temporal generada para su primer acceso.
 */
class CredencialesAccesoMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly SolicitudRegistro $solicitud,
        public readonly User $usuario,
        public readonly string $contrasenaPlana,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '¡Tu solicitud fue aprobada! Credenciales de acceso — BovWeight CR',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.credenciales-acceso',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
