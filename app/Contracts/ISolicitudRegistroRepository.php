<?php

namespace App\Contracts;

use App\Models\SolicitudRegistro;
use Illuminate\Support\Collection;

/**
 * Repository interface for SolicitudRegistro persistence.
 * Isolates solicitud query logic from business services.
 */
interface ISolicitudRegistroRepository
{
    public function findById(int $id): ?SolicitudRegistro;

    public function findAll(): Collection;

    public function findPendientes(): Collection;

    public function existsByEmail(string $correo): bool;

    public function save(SolicitudRegistro $solicitud): SolicitudRegistro;
}
