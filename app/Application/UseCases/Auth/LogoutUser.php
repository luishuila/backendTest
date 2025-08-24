<?php

namespace App\Application\UseCases\Auth;

use App\Domain\Services\Auth\AuthServiceInterface;

final class LogoutUser
{
    public function __construct(private AuthServiceInterface $auth) {}

    public function __invoke(): void
    {
        $this->auth->logout();
    }
}
