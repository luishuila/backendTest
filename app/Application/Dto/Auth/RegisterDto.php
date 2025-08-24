<?php

namespace App\Application\Dto\Auth;

class RegisterDto
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
    ) {}
}
