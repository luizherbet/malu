<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Services\JwtService;
use App\Services\MaluAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        private MaluAuthService $maluAuth,
        private JwtService $jwt,
    ) {}

    public function user(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json(['data' => null]);
        }

        return response()->json([
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = $this->maluAuth->authenticate(
            $request->validated('email'),
            $request->validated('password'),
        );

        if ($user === null) {
            throw ValidationException::withMessages([
                'email' => ['Credenciais inválidas.'],
            ]);
        }

        return response()->json([
            'data' => [
                'token' => $this->jwt->issue($user),
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ],
        ]);
    }

    public function logout(): JsonResponse
    {
        return response()->json(['message' => 'Logged out.']);
    }
}
