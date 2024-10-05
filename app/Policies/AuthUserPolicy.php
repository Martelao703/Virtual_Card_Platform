<?php

namespace App\Policies;

use App\Models\AuthUser;

class AuthUserPolicy
{
    public function viewAny(AuthUser $authUser)
    {
        return $authUser->user_type === 'A';
    }

    public function viewMe(AuthUser $authUser)
    {
        return $authUser->exists();
    }
}