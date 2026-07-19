<?php

namespace App\Observers;

use App\Models\User;

class UserObserver
{
    public function deleting(User $user): void
    {
        if ($user->avatar_path && class_exists(\App\Services\AvatarService::class)) {
            app(\App\Services\AvatarService::class)->deleteExisting($user);
        }
    }
}
