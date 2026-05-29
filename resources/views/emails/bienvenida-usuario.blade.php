<x-mail::message>
# ¡Bienvenido a BovWeight CR!

Hola, **{{ $usuario->nombre }}**.

El administrador del sistema ha creado tu cuenta en **BovWeight CR**. Ya puedes acceder a la plataforma con las siguientes credenciales:

<x-mail::panel>
**Correo:** {{ $usuario->correo }}

**Contraseña temporal:** `{{ $contrasenaPlana }}`
</x-mail::panel>

> **Importante:** Por seguridad, te recomendamos cambiar tu contraseña en tu primer inicio de sesión.

<x-mail::button :url="config('app.url')" color="green">
Iniciar sesión
</x-mail::button>

Si tienes alguna duda, comunícate con el administrador del sistema.

Saludos,<br>
**Equipo BovWeight CR**
</x-mail::message>
