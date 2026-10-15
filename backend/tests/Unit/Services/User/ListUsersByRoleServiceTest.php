<?php

namespace Tests\Unit\Services\User;

use App\Enums\UserRole;
use App\Http\Services\User\ListUsersByRoleService;
use App\Interfaces\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class ListUsersByRoleServiceTest extends TestCase
{
    public function test_returns_users_of_the_requested_role_from_repository(): void
    {
        $students = new Collection([
            new User(['name' => 'Ana Souza', 'role' => UserRole::Student]),
            new User(['name' => 'Bruno Lima', 'role' => UserRole::Student]),
        ]);

        $repository = Mockery::mock(UserRepositoryInterface::class, function (MockInterface $mock) use ($students) {
            $mock->shouldReceive('getByRole')->once()->with(UserRole::Student)->andReturn($students);
        });

        $result = (new ListUsersByRoleService($repository))->handle(UserRole::Student);

        $this->assertSame($students, $result);
    }

    public function test_forwards_teacher_role_to_repository(): void
    {
        $repository = Mockery::mock(UserRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('getByRole')->once()->with(UserRole::Teacher)->andReturn(new Collection);
        });

        $result = (new ListUsersByRoleService($repository))->handle(UserRole::Teacher);

        $this->assertTrue($result->isEmpty());
    }
}
