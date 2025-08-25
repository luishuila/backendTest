<?php

namespace App\Application\UseCases\Auth;

use App\Application\Dto\Auth\LoginDto;
use App\Domain\Services\Auth\AuthServiceInterface;

final class LoginUser
{
    public function __construct(private AuthServiceInterface $auth) {}

    public function __invoke(LoginDto $dto): array
    {
        return $this->auth->attempt($dto->email, $dto->password);
    }
}
