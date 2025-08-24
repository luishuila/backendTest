<?php

namespace App\Application\UseCases\Auth;

use App\Domain\Services\Auth\AuthServiceInterface;

final class MeUser
{
    public function __construct(private AuthServiceInterface $auth) {}

    public function __invoke(): mixed
    {
        return $this->auth->currentUser();
    }
}
