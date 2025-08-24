<?php

namespace App\Providers;

use App\Domain\Repositories\TaskRepositoryInterface;
use Illuminate\Support\ServiceProvider;
use App\Domain\Repositories\UserRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentUserRepository;
use App\Domain\Services\Auth\AuthServiceInterface;
use App\Infrastructure\Auth\JwtAuthService;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentTaskRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {

        $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);
        $this->app->bind(TaskRepositoryInterface::class, EloquentTaskRepository::class);
        $this->app->bind(AuthServiceInterface::class, JwtAuthService::class);
    }
}
