<?php

namespace Tests\Feature\User;

use App\Enums\UserRole;
use App\Interfaces\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Mockery\MockInterface;
use Tests\TestCase;

class ListTeachersTest extends TestCase
{
    public function test_lists_teachers_in_standard_envelope(): void
    {
        $this->mock(UserRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('getByRole')->once()->with(UserRole::Teacher)->andReturn(new Collection([
                User::factory()->teacher()->make(['id' => 1, 'name' => 'Professor Fênix', 'email' => 'teacher@fenix.local']),
            ]));
        });

        $this->getJson('/api/v1/teachers')
            ->assertOk()
            ->assertExactJson([
                'message' => 'Professores listados com sucesso.',
                'status' => 200,
                'data' => [
                    ['id' => 1, 'name' => 'Professor Fênix', 'email' => 'teacher@fenix.local'],
                ],
            ]);
    }
}
