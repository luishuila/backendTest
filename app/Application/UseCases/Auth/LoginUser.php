<?php

namespace App\Application\UseCases\Auth;

use App\Application\Dto\Auth\LoginDTO;
use App\Domain\Services\Auth\AuthServiceInterface;

final class LoginUser
{
    public function __construct(private AuthServiceInterface $auth) {}

    /** @return array{access_token:string, token_type:string, expires_in:int, user:mixed} */
    public function __invoke(LoginDTO $dto): array
    {
        return $this->auth->attempt($dto->email, $dto->password);
    }
}
