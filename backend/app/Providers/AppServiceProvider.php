<?php

namespace App\Providers;

use App\Enums\UserRole;
use App\Http\Repositories\AttemptRepository;
use App\Http\Repositories\DashboardRepository;
use App\Http\Repositories\ExamRepository;
use App\Http\Repositories\UserRepository;
use App\Interfaces\AttemptRepositoryInterface;
use App\Interfaces\DashboardRepositoryInterface;
use App\Interfaces\ExamRepositoryInterface;
use App\Interfaces\UserRepositoryInterface;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Repository bindings: Interface => Repository.
     *
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        UserRepositoryInterface::class => UserRepository::class,
        ExamRepositoryInterface::class => ExamRepository::class,
        AttemptRepositoryInterface::class => AttemptRepository::class,
        DashboardRepositoryInterface::class => DashboardRepository::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void {}

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Route::pattern('teacher', '[0-9]+');
        Route::pattern('student', '[0-9]+');

        Route::bind('teacher', fn (string $id) => $this->app->make(UserRepositoryInterface::class)
            ->findByRole((int) $id, UserRole::Teacher));
        Route::bind('student', fn (string $id) => $this->app->make(UserRepositoryInterface::class)
            ->findByRole((int) $id, UserRole::Student));
    }
}
