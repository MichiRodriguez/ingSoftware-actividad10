<?php

namespace App\Mail;

use App\Models\SolicitudRegistro;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Correo enviado al solicitante cuando el administrador rechaza su solicitud,
 * incluyendo el motivo del rechazo para que pueda subsanarlo.
 */
class RechazoSolicitudMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly SolicitudRegistro $solicitud,
        public readonly string $motivoRechazo,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Actualización sobre tu solicitud de registro — BovWeight CR',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.rechazo-solicitud',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
