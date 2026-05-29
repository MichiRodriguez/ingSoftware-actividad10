<?php

namespace App\Repositories;

use App\Contracts\IUserRepository;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Eloquent implementation of IUserRepository.
 * All Eloquent / SQL details are confined here.
 * Swap this class for DoctrineUserRepository or InMemoryUserRepository
 * without touching any service or controller.
 */
class EloquentUserRepository implements IUserRepository
{
    public function findById(int $id): ?User
    {
        return User::with('tipoUsuario')->find($id);
    }

    public function findByEmail(string $correo): ?User
    {
        return User::with('tipoUsuario')->where('correo', $correo)->first();
    }

    public function findAll(?string $search = null): Collection
    {
        return User::with('tipoUsuario')
            ->when($search, fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                    ->orWhere('correo', 'like', "%{$search}%");
            }))
            ->get();
    }

    public function existsByEmail(string $correo, ?int $excludeId = null): bool
    {
        return User::where('correo', $correo)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->exists();
    }

    public function save(User $user): User
    {
        $user->save();

        return $user->fresh('tipoUsuario');
    }

    public function delete(int $id): void
    {
        User::findOrFail($id)->delete();
    }
}
