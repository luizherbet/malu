<?php

namespace App\Services;

use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Throwable;

class JwtService
{
    public function issue(User $user): string
    {
        $now = time();
        $ttl = config('malu.jwt_ttl_minutes', 10080) * 60;

        return JWT::encode([
            'sub' => $user->id,
            'iat' => $now,
            'exp' => $now + $ttl,
        ], $this->secret(), 'HS256');
    }

    public function resolveUserId(string $token): ?int
    {
        try {
            $payload = JWT::decode($token, new Key($this->secret(), 'HS256'));

            return isset($payload->sub) ? (int) $payload->sub : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function secret(): string
    {
        $secret = config('malu.jwt_secret');

        if (! is_string($secret) || $secret === '') {
            throw new \RuntimeException('JWT secret is not configured (MALU_JWT_SECRET or APP_KEY).');
        }

        if (str_starts_with($secret, 'base64:')) {
            $decoded = base64_decode(substr($secret, 7), true);

            if ($decoded !== false && strlen($decoded) >= 32) {
                return $decoded;
            }
        }

        if (strlen($secret) < 32) {
            return hash('sha256', $secret, true);
        }

        return $secret;
    }
}
