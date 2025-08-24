<?php

namespace App\Domain\Services\Auth;

use App\Domain\Entities\User;

interface AuthServiceInterface
{

    public function attempt(string $email, string $password): array;


    public function loginFromUser(User $user): array;


    public function refresh(): array;

    public function logout(): void;

 
    public function currentUser(): mixed;

    public function ttlSeconds(): int;
}
