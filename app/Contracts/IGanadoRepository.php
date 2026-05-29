<?php

namespace App\Contracts;

use App\Models\Ganado;
use Illuminate\Support\Collection;

interface IGanadoRepository
{
    public function findById(int $id): ?Ganado;

    public function findAllByUsuario(int $usuarioId): Collection;

    public function findByFincaId(int $fincaId): Collection;

    public function existsByArete(string $arete, ?int $excludeId = null): bool;

    public function save(Ganado $ganado): Ganado;

    public function delete(int $id): void;
}
