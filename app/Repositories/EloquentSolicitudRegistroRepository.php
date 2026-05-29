<?php

namespace App\Repositories;

use App\Contracts\ISolicitudRegistroRepository;
use App\Models\SolicitudRegistro;
use Illuminate\Support\Collection;

/**
 * Eloquent implementation of ISolicitudRegistroRepository.
 */
class EloquentSolicitudRegistroRepository implements ISolicitudRegistroRepository
{
    public function findById(int $id): ?SolicitudRegistro
    {
        return SolicitudRegistro::with('estado')->find($id);
    }

    public function findAll(): Collection
    {
        return SolicitudRegistro::with('estado')->latest()->get();
    }

    public function findPendientes(): Collection
    {
        return SolicitudRegistro::with('estado')
            ->whereHas('estado', fn ($q) => $q->where('nombre', 'Pendiente'))
            ->latest()
            ->get();
    }

    public function existsByEmail(string $correo): bool
    {
        return SolicitudRegistro::where('correo', $correo)->exists();
    }

    public function save(SolicitudRegistro $solicitud): SolicitudRegistro
    {
        $solicitud->save();

        return $solicitud->fresh('estado');
    }
}
