<?php

namespace App\Services;

use App\Contracts\IUserFactory;
use App\Contracts\IUserRepository;
use App\Events\UsuarioCreado;
use App\Models\User;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Lógica de negocio del CRUD de usuarios (SRP).
 *
 * Depende de IUserRepository (Repository) e IUserFactory (Factory)
 * mediante inyección de dependencias (DIP).
 * Dispara UsuarioCreado al Observer para notificaciones (Observer).
 */
class UsuarioService
{
    public function __construct(
        private readonly IUserRepository $usuarios,
        private readonly IUserFactory $userFactory,
    ) {}

    public function listar(?string $search = null)
    {
        return $this->usuarios->findAll($search);
    }

    public function obtener(int $id): User
    {
        $usuario = $this->usuarios->findById($id);

        if (! $usuario) {
            throw new NotFoundHttpException('Usuario no encontrado.');
        }

        return $usuario;
    }

    /**
     * Crea un usuario desde el panel de administración (HU-01.4).
     * Usa el Factory para centralizar la creación por tipo.
     * Dispara UsuarioCreado para que los Observers notifiquen al nuevo usuario.
     */
    public function crear(string $tipoNombre, array $datos): User
    {
        if ($this->usuarios->existsByEmail($datos['correo'])) {
            throw new ConflictHttpException('Ya existe un usuario con ese correo.');
        }

        $contrasenaPlana = $datos['contrasena'] ?? Str::random(12);
        $datos['contrasena'] = $contrasenaPlana;

        // FACTORY: centraliza la creación por tipo, sin new User() disperso
        $usuario = $this->userFactory->make($tipoNombre, $datos);

        // OBSERVER: notifica a los listeners sin acoplar el servicio a ellos
        UsuarioCreado::dispatch($usuario, $contrasenaPlana);

        return $usuario->load('tipoUsuario');
    }

    /**
     * Actualiza datos de un usuario existente (HU-01.6).
     */
    public function actualizar(int $id, array $datos): User
    {
        $usuario = $this->obtener($id);

        if (
            isset($datos['correo']) &&
            $this->usuarios->existsByEmail($datos['correo'], $id)
        ) {
            throw new ConflictHttpException('Ese correo ya está en uso por otro usuario.');
        }

        $usuario->fill($datos);

        return $this->usuarios->save($usuario);
    }

    /**
     * Elimina un usuario y revoca todos sus tokens (HU-01.7).
     */
    public function eliminar(int $id): void
    {
        $usuario = $this->obtener($id);
        $usuario->tokens()->delete();
        $this->usuarios->delete($id);
    }
}
