<?php

namespace Tests\Feature\User;

use App\Enums\UserRole;
use App\Interfaces\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Mockery\MockInterface;
use Tests\TestCase;

class ListStudentsTest extends TestCase
{
    public function test_lists_students_in_standard_envelope(): void
    {
        $students = new Collection([
            User::factory()->student()->make(['id' => 2, 'name' => 'Ana Souza', 'email' => 'ana.souza@fenix.local']),
            User::factory()->student()->make(['id' => 3, 'name' => 'Bruno Lima', 'email' => 'bruno.lima@fenix.local']),
        ]);

        $this->mock(UserRepositoryInterface::class, function (MockInterface $mock) use ($students) {
            $mock->shouldReceive('getByRole')->once()->with(UserRole::Student)->andReturn($students);
        });

        $this->getJson('/api/v1/students')
            ->assertOk()
            ->assertExactJson([
                'message' => 'Alunos listados com sucesso.',
                'status' => 200,
                'data' => [
                    ['id' => 2, 'name' => 'Ana Souza', 'email' => 'ana.souza@fenix.local'],
                    ['id' => 3, 'name' => 'Bruno Lima', 'email' => 'bruno.lima@fenix.local'],
                ],
            ]);
    }

    public function test_does_not_expose_sensitive_fields(): void
    {
        $this->mock(UserRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('getByRole')->andReturn(new Collection([
                User::factory()->student()->make(['id' => 2]),
            ]));
        });

        $student = $this->getJson('/api/v1/students')->json('data.0');

        $this->assertSame(['id', 'name', 'email'], array_keys($student));
    }

    public function test_returns_empty_list_when_there_are_no_students(): void
    {
        $this->mock(UserRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('getByRole')->andReturn(new Collection);
        });

        $this->getJson('/api/v1/students')
            ->assertOk()
            ->assertJsonPath('data', []);
    }
}
