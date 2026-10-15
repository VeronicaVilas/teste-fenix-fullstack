<?php

namespace App\Interfaces;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface UserRepositoryInterface
{
    /**
     * @return Collection<int, User>
     */
    public function getByRole(UserRole $role): Collection;

    public function findByRole(int $id, UserRole $role): User;
}
