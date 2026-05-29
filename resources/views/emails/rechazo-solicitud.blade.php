<x-mail::message>
# Actualización sobre tu solicitud

Hola, **{{ $solicitud->nombre }} {{ $solicitud->apellidos }}**.

Hemos revisado tu solicitud de registro en **BovWeight CR** y, lamentablemente, no hemos podido aprobarla en este momento.

<x-mail::panel>
**Motivo del rechazo:**

{{ $motivoRechazo }}
</x-mail::panel>

Si consideras que existe un error o deseas corregir la información enviada, puedes volver a enviar tu solicitud una vez hayas subsanado el inconveniente.

Si tienes alguna pregunta, no dudes en contactar al administrador del sistema.

Saludos,<br>
**Equipo BovWeight CR**
</x-mail::message>
