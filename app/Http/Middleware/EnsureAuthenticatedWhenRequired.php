<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAuthenticatedWhenRequired
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('malu.require_auth') && ! $request->user()) {
            return response()->json([
                'message' => 'Authentication required.',
            ], 401);
        }

        return $next($request);
    }
}
