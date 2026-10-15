<?php

namespace Tests\Feature\User;

use App\Enums\UserRole;
use App\Interfaces\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Route;
use Mockery\MockInterface;
use Tests\TestCase;

class RoleRouteBindingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::prefix('api/v1/testing')->middleware('api')->group(function () {
            Route::get('teachers/{teacher}', fn (User $teacher) => ['id' => $teacher->id]);
            Route::get('students/{student}', fn (User $student) => ['id' => $student->id]);
        });
    }

    public function test_teacher_parameter_resolves_teacher(): void
    {
        $this->mock(UserRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('findByRole')->once()->with(1, UserRole::Teacher)
                ->andReturn(User::factory()->teacher()->make(['id' => 1]));
        });

        $this->getJson('/api/v1/testing/teachers/1')
            ->assertOk()
            ->assertJsonPath('id', 1);
    }

    public function test_student_parameter_resolves_student(): void
    {
        $this->mock(UserRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('findByRole')->once()->with(2, UserRole::Student)
                ->andReturn(User::factory()->student()->make(['id' => 2]));
        });

        $this->getJson('/api/v1/testing/students/2')
            ->assertOk()
            ->assertJsonPath('id', 2);
    }

    public function test_user_with_another_role_returns_404(): void
    {
        $this->mock(UserRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('findByRole')->once()->with(1, UserRole::Student)
                ->andThrow(new ModelNotFoundException);
        });

        $this->getJson('/api/v1/testing/students/1')
            ->assertNotFound()
            ->assertJsonPath('message', 'Recurso não encontrado.');
    }

    public function test_non_numeric_id_returns_404_without_lookup(): void
    {
        $this->mock(UserRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldNotReceive('findByRole');
        });

        $this->getJson('/api/v1/testing/students/abc')->assertNotFound();
    }
}
