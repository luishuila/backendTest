<?php

namespace App\Infrastructure\Auth;

use App\Domain\Entities\User as UserEntity;
use App\Domain\Services\Auth\AuthServiceInterface;
use Illuminate\Support\Facades\Auth;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

final class JwtAuthService implements AuthServiceInterface
{
    public function attempt(string $email, string $password): array
    {
        $credentials = ['email' => $email, 'password' => $password];

        if (!$token = Auth::guard('api')->attempt($credentials)) {
            abort(401, 'Credenciales inválidas');
        }

        return $this->tokenPayload($token, Auth::guard('api')->user());
    }

    public function loginFromUser(UserEntity $user): array
    {
        $model = \App\Models\User::findOrFail($user->id);
        $token = Auth::guard('api')->login($model);

        if (!$token) {
            abort(500, 'No se pudo generar el token');
        }

        return $this->tokenPayload($token, $model);
    }

    public function refresh(): array
    {
        $newToken = JWTAuth::refresh(JWTAuth::getToken());
        JWTAuth::setToken($newToken);
        $user = JWTAuth::user();

        return $this->tokenPayload($newToken, $user);
    }

    public function logout(): void
    {
        Auth::guard('api')->logout();
    }

    public function currentUser(): mixed
    {
        return Auth::guard('api')->user();
    }

    public function ttlSeconds(): int
    {

        return (int) config('jwt.ttl') * 120;
    }


    private function tokenPayload(string $token, mixed $user): array
    {
        return [
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => $this->ttlSeconds(),
            'user'         => $user,
        ];
    }
}
