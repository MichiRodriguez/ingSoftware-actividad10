<?php

namespace App\Services;

use App\Contracts\IUserRepository;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

/**
 * Encapsula la lógica de autenticación (SRP).
 * Depende de IUserRepository, no de Eloquent directamente (DIP).
 */
class AuthService
{
    public function __construct(
        private readonly IUserRepository $usuarios,
    ) {}

    /**
     * Autentica con correo y contraseña, devuelve el token Sanctum.
     *
     * @throws AuthenticationException
     */
    public function login(string $correo, string $contrasena): array
    {
        $usuario = $this->usuarios->findByEmail($correo);

        if (! $usuario || ! Hash::check($contrasena, $usuario->contrasena)) {
            throw new AuthenticationException('Credenciales incorrectas.');
        }

        $token = $usuario->createToken('api-token')->plainTextToken;

        return [
            'token' => $token,
            'usuario' => $usuario,
        ];
    }

    /**
     * Revoca el token actual del usuario autenticado.
     */
    public function logout($usuario): void
    {
        $usuario->currentAccessToken()->delete();
    }

    /**
     * Genera y almacena un token de recuperación de contraseña (HU-01.3).
     * Expira en 30 minutos según ADR-005.
     *
     * @throws \RuntimeException si el correo no pertenece a ningún usuario.
     */
    public function enviarRecuperacion(string $correo): void
    {
        $usuario = $this->usuarios->findByEmail($correo);

        if (! $usuario) {
            // No revelar si el correo existe (seguridad)
            return;
        }

        Password::broker()->sendResetLink(['correo' => $correo]);
    }

    /**
     * Restablece la contraseña validando el token (expira en 30 min).
     *
     * @throws \RuntimeException si el token es inválido o expiró.
     */
    public function resetearContrasena(string $correo, string $token, string $nuevaContrasena): void
    {
        $estado = Password::broker()->reset(
            ['correo' => $correo, 'password' => $nuevaContrasena, 'password_confirmation' => $nuevaContrasena, 'token' => $token],
            function ($usuario, $contrasena) {
                $usuario->contrasena = $contrasena;
                $usuario->save();
                $usuario->tokens()->delete(); // Invalida sesiones anteriores
            }
        );

        if ($estado !== Password::PASSWORD_RESET) {
            throw new \RuntimeException('Token inválido o expirado.');
        }
    }
}
