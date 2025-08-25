<?php

namespace App\Http\Controllers\Api\V1;


use App\Application\Dto\Auth\RegisterDto;
use App\Application\UseCases\Auth\{LoginUser, RegisterUser, RefreshToken, LogoutUser, MeUser};
use App\Http\Controllers\Controller;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use Illuminate\Http\JsonResponse;


class AuthController extends Controller
{
    public function register(RegisterRequest $req, RegisterUser $useCase): JsonResponse
    {

        $dto = new RegisterDto($req->string('name'), $req->string('email'), $req->string('password'));
        $payload = ($useCase)($dto);
        return response()->json($payload, 201);
    }

    public function login(LoginRequest $request, LoginUser $useCase): JsonResponse
    {

        $dto = $request->toDto();
        $payload = $useCase($dto);
        if (!$payload) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }
        return response()->json($payload, 200);
    }


    public function me(MeUser $useCase): JsonResponse
    {
        return response()->json(($useCase)());
    }

    public function logout(LogoutUser $useCase): JsonResponse
    {
        ($useCase)();
        return response()->json(['message' => 'Sesión cerrada']);
    }

    public function refresh(RefreshToken $useCase): JsonResponse
    {
        return response()->json(($useCase)());
    }
}
