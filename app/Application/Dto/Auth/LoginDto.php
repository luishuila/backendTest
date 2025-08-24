<?php

namespace App\Application\Dto\Auth;
class LoginDTO
{
    public function __construct(
        public string $email,
        public string $password,
        public bool $remember = false,
    ) {}
}
