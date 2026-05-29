<?php

namespace App\Services;

use App\Contracts\IFincaFactory;
use App\Contracts\IFincaRepository;
use App\Models\Finca;
use App\Models\User;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Lógica de negocio del CRUD de fincas.
 */
class FincaService
{
    public function __construct(
        private readonly IFincaRepository $fincas,
        private readonly IFincaFactory $fincaFactory,
    ) {}

    public function listar(User $user): Collection
    {
        $user->loadMissing('tipoUsuario');

        if ($user->esAdministrador()) {
            return $this->fincas->findAll();
        }

        if ($user->tipoUsuario?->nombre === 'Veterinario') {
            return $this->fincas->findByVeterinarioId($user->id);
        }

        return $this->fincas->findByUsuarioId($user->id);
    }

    public function obtener(int $id): Finca
    {
        $finca = $this->fincas->findById($id);

        if (! $finca) {
            throw new NotFoundHttpException('Finca no encontrada.');
        }

        return $finca;
    }

    public function crear(array $datos): Finca
    {
        if ($this->fincas->existsByNumeroFinca($datos['numero_finca'])) {
            throw new ConflictHttpException('Ya existe una finca con ese número.');
        }

        $finca = $this->fincaFactory->make($datos);

        return $this->fincas->save($finca);
    }

    public function actualizar(int $id, array $datos): Finca
    {
        $finca = $this->obtener($id);

        if ($this->fincas->existsByNumeroFinca($datos['numero_finca'], $id)) {
            throw new ConflictHttpException('Ya existe otra finca con ese número.');
        }

        $finca->fill($datos);

        return $this->fincas->save($finca);
    }

    public function eliminar(int $id): void
    {
        $finca = $this->obtener($id);

        if ($finca->ganados()->exists()) {
            throw new BadRequestHttpException('No se puede eliminar la finca porque tiene ganado asociado');
        }

        $this->fincas->delete($id);
    }
}
