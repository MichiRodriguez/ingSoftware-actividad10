<?php

namespace App\Contracts;

use App\Models\Finca;
use Illuminate\Support\Collection;
/**
 * Repository interface for Finca persistence.
 */
interface IFincaRepository
{
    public function findById(int $id): ?Finca;

    public function findAll(): Collection;

    public function findByUsuarioId(int $usuarioId): Collection;

    public function findByVeterinarioId(int $veterinarioId): Collection;

    public function existsByNumeroFinca(string $numeroFinca, ?int $excludeId = null): bool;

    public function save(Finca $finca): Finca;

    public function delete(int $id): void;
}
