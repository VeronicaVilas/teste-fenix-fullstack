<?php

namespace Tests\Feature\Dashboard;

use App\Enums\UserRole;
use App\Interfaces\DashboardRepositoryInterface;
use App\Interfaces\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery\MockInterface;
use Tests\TestCase;

class DashboardExamAveragesTest extends TestCase
{
    private const URL = '/api/v1/teachers/1/dashboard/exam-averages';

    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.default' => 'array']);

        $this->mock(UserRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('findByRole')->with(1, UserRole::Teacher)
                ->andReturn(User::factory()->teacher()->make(['id' => 1]));
        });
    }

    public function test_returns_the_average_of_each_published_exam_paginated(): void
    {
        $this->mock(DashboardRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('examAverages')->once()->with(1, null, 2, 2)->andReturn(new LengthAwarePaginator([
                ['id' => 6, 'title' => 'Prova de Geografia', 'attempts_count' => 4, 'average_percentage' => '62.5'],
                ['id' => 44, 'title' => 'Matemática Básica', 'attempts_count' => 0, 'average_percentage' => null],
            ], 3, 2, 2));
        });

        $this->getJson(self::URL.'?page=2&per_page=2')
            ->assertOk()
            ->assertExactJson([
                'message' => 'Médias das provas carregadas com sucesso.',
                'status' => 200,
                'data' => [
                    'items' => [
                        ['id' => 6, 'title' => 'Prova de Geografia', 'attempts_count' => 4, 'average_percentage' => '62.50'],
                        ['id' => 44, 'title' => 'Matemática Básica', 'attempts_count' => 0, 'average_percentage' => null],
                    ],
                    'meta' => ['current_page' => 2, 'last_page' => 2, 'per_page' => 2, 'total' => 3],
                ],
            ]);
    }

    public function test_forwards_the_trimmed_search(): void
    {
        $this->mock(DashboardRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('examAverages')->once()->with(1, 'Geografia', 1, 10)
                ->andReturn(new LengthAwarePaginator([], 0, 10, 1));
        });

        $this->getJson(self::URL.'?search=%20Geografia%20')->assertOk()->assertJsonPath('data.items', []);
    }

    public function test_validates_the_query(): void
    {
        $this->mock(DashboardRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldNotReceive('examAverages');
        });

        $this->getJson(self::URL.'?per_page=51')
            ->assertUnprocessable()
            ->assertJsonPath('errors.per_page.0', 'A quantidade por página deve ser no máximo 50.');

        $this->getJson(self::URL.'?search='.str_repeat('a', 256))
            ->assertUnprocessable()
            ->assertJsonPath('errors.search.0', 'A busca deve ter no máximo 255 caracteres.');
    }

    public function test_returns_404_when_user_is_not_a_teacher(): void
    {
        $this->mock(UserRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('findByRole')->with(3, UserRole::Teacher)->andThrow(new ModelNotFoundException);
        });

        $this->getJson('/api/v1/teachers/3/dashboard/exam-averages')->assertNotFound();
    }
}
