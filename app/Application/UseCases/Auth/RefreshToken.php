<?php

namespace App\Application\UseCases\Auth;

use App\Domain\Services\Auth\AuthServiceInterface;

final class RefreshToken
{
    public function __construct(private AuthServiceInterface $auth) {}

    public function __invoke(): array
    {
        return $this->auth->refresh();
    }
}
