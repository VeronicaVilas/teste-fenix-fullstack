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

class DashboardExamsRankingTest extends TestCase
{
    private const URL = '/api/v1/teachers/1/dashboard/exams-ranking';

    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.default' => 'array']);

        $this->mock(UserRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('findByRole')->with(1, UserRole::Teacher)
                ->andReturn(User::factory()->teacher()->make(['id' => 1]));
        });
    }

    public function test_returns_exams_ranked_by_average(): void
    {
        $this->mock(DashboardRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('examsRanking')->once()->with(1, 2, 2)->andReturn(new LengthAwarePaginator([
                ['position' => '3', 'exam_id' => '12', 'title' => 'Prova de Química', 'students_count' => '4', 'average_percentage' => '62.5', 'best_student_name' => 'Ana Souza'],
                ['position' => '4', 'exam_id' => '13', 'title' => 'Prova de Física', 'students_count' => '1', 'average_percentage' => 25, 'best_student_name' => 'Carla Mendes'],
            ], 5, 2, 2));
        });

        $this->getJson(self::URL.'?page=2&per_page=2')
            ->assertOk()
            ->assertExactJson([
                'message' => 'Ranking das provas carregado com sucesso.',
                'status' => 200,
                'data' => [
                    'items' => [
                        ['position' => 3, 'exam_id' => 12, 'title' => 'Prova de Química', 'students_count' => 4, 'average_percentage' => '62.50', 'best_student_name' => 'Ana Souza'],
                        ['position' => 4, 'exam_id' => 13, 'title' => 'Prova de Física', 'students_count' => 1, 'average_percentage' => '25.00', 'best_student_name' => 'Carla Mendes'],
                    ],
                    'meta' => ['current_page' => 2, 'last_page' => 3, 'per_page' => 2, 'total' => 5],
                ],
            ]);
    }

    public function test_uses_first_page_and_ten_items_by_default(): void
    {
        $this->mock(DashboardRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('examsRanking')->once()->with(1, 1, 10)
                ->andReturn(new LengthAwarePaginator([], 0, 10, 1));
        });

        $this->getJson(self::URL)
            ->assertOk()
            ->assertJsonPath('data.items', []);
    }

    public function test_validates_pagination(): void
    {
        $this->getJson(self::URL.'?per_page=100')
            ->assertUnprocessable()
            ->assertJsonPath('errors.per_page.0', 'A quantidade por página deve ser no máximo 50.');
    }

    public function test_returns_404_for_unknown_teacher(): void
    {
        $this->mock(UserRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('findByRole')->andThrow(new ModelNotFoundException);
        });

        $this->getJson(self::URL)->assertNotFound();
    }
}
