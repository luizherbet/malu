<?php

namespace App\Policies;

use App\Models\Download;
use App\Models\User;

class DownloadPolicy
{
    public function view(?User $user, Download $download): bool
    {
        if ($download->user_id === null) {
            return true;
        }

        return $user !== null && $user->id === $download->user_id;
    }

    public function create(?User $user): bool
    {
        if (! config('malu.require_auth')) {
            return true;
        }

        return $user !== null;
    }
}
