<?php

namespace Tests\Feature\StudentExam;

use App\Enums\UserRole;
use App\Interfaces\DashboardRepositoryInterface;
use App\Interfaces\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Mockery\MockInterface;
use Tests\TestCase;

class StudentDashboardTest extends TestCase
{
    private const URL = '/api/v1/students/3/dashboard';

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(UserRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('findByRole')->with(3, UserRole::Student)
                ->andReturn(User::factory()->student()->make(['id' => 3]));
        });
    }

    public function test_returns_the_student_summary(): void
    {
        $this->mock(DashboardRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('studentSummary')->once()->with(3)->andReturn([
                'answered_count' => 3,
                'pending_count' => 5,
                'average_percentage' => '41.666',
                'best_percentage' => '75.00',
            ]);
        });

        $this->getJson(self::URL)
            ->assertOk()
            ->assertExactJson([
                'message' => 'Dashboard carregado com sucesso.',
                'status' => 200,
                'data' => [
                    'answered_count' => 3,
                    'pending_count' => 5,
                    'average_percentage' => '41.67',
                    'best_percentage' => '75.00',
                ],
            ]);
    }

    public function test_returns_null_percentages_before_any_attempt(): void
    {
        $this->mock(DashboardRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('studentSummary')->once()->with(3)->andReturn([
                'answered_count' => 0,
                'pending_count' => 8,
                'average_percentage' => null,
                'best_percentage' => null,
            ]);
        });

        $this->getJson(self::URL)
            ->assertOk()
            ->assertJsonPath('data.answered_count', 0)
            ->assertJsonPath('data.pending_count', 8)
            ->assertJsonPath('data.average_percentage', null)
            ->assertJsonPath('data.best_percentage', null);
    }

    public function test_returns_404_when_user_is_not_a_student(): void
    {
        $this->mock(UserRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('findByRole')->with(1, UserRole::Student)->andThrow(new ModelNotFoundException);
        });
        $this->mock(DashboardRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldNotReceive('studentSummary');
        });

        $this->getJson('/api/v1/students/1/dashboard')->assertNotFound();
    }
}
