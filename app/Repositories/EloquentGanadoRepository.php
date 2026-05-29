<?php

namespace App\Repositories;

use App\Contracts\IGanadoRepository;
use App\Models\Ganado;
use Illuminate\Support\Collection;

class EloquentGanadoRepository implements IGanadoRepository
{
    public function findById(int $id): ?Ganado
    {
        return Ganado::with(['estadoSalud', 'estadoComercial'])->find($id);
    }

    public function findAllByUsuario(int $usuarioId): Collection
    {
        return Ganado::with(['estadoSalud', 'estadoComercial', 'finca'])
            ->whereHas('finca', fn ($q) => $q->where('usuario_id', $usuarioId))
            ->latest()
            ->get();
    }

    public function findByFincaId(int $fincaId): Collection
    {
        return Ganado::with(['estadoSalud', 'estadoComercial', 'finca'])
            ->where('finca_id', $fincaId)
            ->latest()
            ->get();
    }

    public function existsByArete(string $arete, ?int $excludeId = null): bool
    {
        return Ganado::where('arete', $arete)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->exists();
    }

    public function save(Ganado $ganado): Ganado
    {
        $ganado->save();

        return $ganado->fresh(['estadoSalud', 'estadoComercial']);
    }

    public function delete(int $id): void
    {
        Ganado::findOrFail($id)->delete();
    }
}
