<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\JwtService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateJwt
{
    public function __construct(private JwtService $jwt) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('malu.require_auth')) {
            return $next($request);
        }

        $token = $request->bearerToken();

        if ($token === null || $token === '') {
            return response()->json(['message' => 'Authentication required.'], 401);
        }

        $userId = $this->jwt->resolveUserId($token);

        if ($userId === null) {
            return response()->json(['message' => 'Invalid or expired token.'], 401);
        }

        $user = User::query()->find($userId);

        if ($user === null) {
            return response()->json(['message' => 'Invalid or expired token.'], 401);
        }

        Auth::setUser($user);
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
