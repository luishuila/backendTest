<?php

namespace App\Application\UseCases\Auth;

use App\Application\Dto\Auth\RegisterDTO;
use App\Domain\Entities\User as UserEntity;
use App\Domain\Repositories\UserRepositoryInterface;
use App\Domain\Services\Auth\AuthServiceInterface;
use Illuminate\Support\Facades\Hash;

final class RegisterUser
{
    public function __construct(
        private UserRepositoryInterface $users,
        private AuthServiceInterface $auth
    ) {}

    public function __invoke(RegisterDTO $dto): array
    {
        $user = new UserEntity(
            id: null,
            name: $dto->name,
            email: $dto->email,
            password: Hash::make($dto->password)
        );

        $created = $this->users->create($user);
        return $this->auth->loginFromUser($created);
    }
}
