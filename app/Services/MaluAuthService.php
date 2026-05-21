<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class MaluAuthService
{
    public function authenticate(string $email, string $password): ?User
    {
        if (strcasecmp($email, config('malu.auth_email')) !== 0) {
            return null;
        }

        $user = $this->ensureUser();

        if (! Hash::check($password, $user->password)) {
            return null;
        }

        return $user;
    }

    public function ensureUser(): User
    {
        $email = config('malu.auth_email');
        $plainPassword = config('malu.auth_password');

        $user = User::query()->firstOrNew(['email' => $email]);

        $user->name = $user->name ?? 'Malu';

        if ($plainPassword !== null && $plainPassword !== '') {
            $user->password = Hash::make($plainPassword);
        }

        $user->save();

        return $user;
    }
}
