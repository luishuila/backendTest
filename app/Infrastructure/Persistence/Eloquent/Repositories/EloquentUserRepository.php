<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Entities\User as UserEntity;
use App\Domain\Repositories\UserRepositoryInterface;
use App\Models\User;

final class EloquentUserRepository implements UserRepositoryInterface
{
    public function findByEmail(string $email): ?UserEntity
    {
        $m = User::where('email', $email)->first();
        return $m ? $this->toEntity($m) : null;
    }

    public function create(UserEntity $user): UserEntity
    {
        $m = User::create([
            'name'     => $user->name,
            'email'    => $user->email,
            'password' => $user->password,
        ]);

        return $this->toEntity($m);
    }

    private function toEntity(User $m): UserEntity
    {
        return new UserEntity(
            id: $m->id,
            name: $m->name,
            email: $m->email,
            password: $m->password
        );
    }
}
