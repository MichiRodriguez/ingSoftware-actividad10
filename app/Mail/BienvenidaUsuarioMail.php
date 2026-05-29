<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Correo enviado cuando el administrador crea un usuario directamente (HU-01.4).
 * Incluye el correo y la contraseña temporal para el primer acceso.
 */
class BienvenidaUsuarioMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $usuario,
        public readonly string $contrasenaPlana,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '¡Bienvenido a BovWeight CR! Tus credenciales de acceso',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.bienvenida-usuario',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
