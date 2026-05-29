<?php

namespace App\Repositories;

use App\Contracts\IFincaRepository;
use App\Models\Finca;
use Illuminate\Support\Collection;

/**
 * Eloquent implementation of IFincaRepository.
 */
class EloquentFincaRepository implements IFincaRepository
{
    public function findById(int $id): ?Finca
    {
        return Finca::with(['usuario', 'ganados'])->find($id);
    }

    public function findAll(): Collection
    {
        return Finca::with(['usuario', 'veterinario'])->latest()->get();
    }

    public function findByUsuarioId(int $usuarioId): Collection
    {
        return Finca::with(['usuario', 'veterinario'])
            ->where('usuario_id', $usuarioId)
            ->latest()
            ->get();
    }

    public function findByVeterinarioId(int $veterinarioId): Collection
    {
        return Finca::with(['usuario', 'veterinario'])
            ->where('veterinario_id', $veterinarioId)
            ->latest()
            ->get();
    }

    public function existsByNumeroFinca(string $numeroFinca, ?int $excludeId = null): bool
    {
        return Finca::where('numero_finca', $numeroFinca)
            ->when($excludeId, fn ($query) => $query->where('id', '!=', $excludeId))
            ->exists();
    }

    public function save(Finca $finca): Finca
    {
        $finca->save();

        return $finca->fresh(['usuario', 'ganados']);
    }

    public function delete(int $id): void
    {
        Finca::findOrFail($id)->delete();
    }
}
