<?php

namespace App\Http\Repositories;

use App\Enums\UserRole;
use App\Interfaces\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class UserRepository implements UserRepositoryInterface
{
    public function getByRole(UserRole $role): Collection
    {
        return User::query()
            ->where('role', $role)
            ->orderBy('name')
            ->get();
    }

    public function findByRole(int $id, UserRole $role): User
    {
        return User::query()
            ->where('role', $role)
            ->findOrFail($id);
    }
}
