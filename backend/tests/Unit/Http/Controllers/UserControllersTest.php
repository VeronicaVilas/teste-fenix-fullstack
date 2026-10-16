<?php

namespace Tests\Unit\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherController;
use App\Http\Services\User\ListUsersByRoleService;
use App\Interfaces\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class UserControllersTest extends TestCase
{
    public function test_teacher_controller_lists_teachers(): void
    {
        $service = $this->service(UserRole::Teacher, User::factory()->teacher()->make(['id' => 1, 'name' => 'Professor Fênix', 'email' => 'teacher@fenix.local']));

        $response = (new TeacherController)->index($service);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'message' => 'Professores listados com sucesso.',
            'status' => 200,
            'data' => [['id' => 1, 'name' => 'Professor Fênix', 'email' => 'teacher@fenix.local']],
        ], $response->getData(true));
    }

    public function test_student_controller_lists_students(): void
    {
        $service = $this->service(UserRole::Student, User::factory()->student()->make(['id' => 3, 'name' => 'Ana Souza', 'email' => 'ana@fenix.local']));

        $response = (new StudentController)->index($service);

        $this->assertSame('Alunos listados com sucesso.', $response->getData(true)['message']);
        $this->assertSame([['id' => 3, 'name' => 'Ana Souza', 'email' => 'ana@fenix.local']], $response->getData(true)['data']);
    }

    private function service(UserRole $role, User $user): ListUsersByRoleService
    {
        return new ListUsersByRoleService(Mockery::mock(UserRepositoryInterface::class, function (MockInterface $mock) use ($role, $user) {
            $mock->shouldReceive('getByRole')->once()->with($role)->andReturn(new Collection([$user]));
        }));
    }
}
