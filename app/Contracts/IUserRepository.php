<?php

namespace App\Contracts;

use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Repository interface for User persistence.
 * Speaks the domain language, hiding all Eloquent/ORM details.
 */
interface IUserRepository
{
    public function findById(int $id): ?User;

    public function findByEmail(string $correo): ?User;

    public function findAll(?string $search = null): Collection;

    public function existsByEmail(string $correo, ?int $excludeId = null): bool;

    public function save(User $user): User;

    public function delete(int $id): void;
}
