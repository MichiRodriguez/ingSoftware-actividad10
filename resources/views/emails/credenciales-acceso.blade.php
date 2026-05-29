<x-mail::message>
# ¡Tu solicitud fue aprobada!

Hola, **{{ $solicitud->nombre }} {{ $solicitud->apellidos }}**.

Nos complace informarte que tu solicitud de registro en **BovWeight CR** ha sido **aprobada**. A partir de ahora puedes acceder a la plataforma con las siguientes credenciales:

<x-mail::panel>
**Correo:** {{ $usuario->correo }}

**Contraseña temporal:** `{{ $contrasenaPlana }}`
</x-mail::panel>

> **Importante:** Por seguridad, te recomendamos cambiar tu contraseña en tu primer inicio de sesión.

<x-mail::button :url="config('app.url')" color="green">
Iniciar sesión
</x-mail::button>

Si tienes alguna duda, no dudes en contactar al administrador del sistema.

Saludos,<br>
**Equipo BovWeight CR**
</x-mail::message>
