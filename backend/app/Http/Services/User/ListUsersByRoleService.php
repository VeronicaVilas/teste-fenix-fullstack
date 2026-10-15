<?php

namespace App\Http\Services\User;

use App\Enums\UserRole;
use App\Interfaces\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

final readonly class ListUsersByRoleService
{
    public function __construct(private UserRepositoryInterface $userRepository) {}

    /**
     * @return Collection<int, User>
     */
    public function handle(UserRole $role): Collection
    {
        return $this->userRepository->getByRole($role);
    }
}
