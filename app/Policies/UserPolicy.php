<?php

namespace App\Policies;

use App\Models\AuthUser;
use App\Models\User;

class UserPolicy
{
    public function viewAny(AuthUser $authUser)
    {
        return $authUser->user_type == 'A';
    }

    public function view(AuthUser $authUser)
    {
        return $authUser->user_type == 'A';
    }

    public function viewMe(AuthUser $authUser)
    {
        return $authUser->exists();
    }

    public function create(AuthUser $authUser)
    {
        return $authUser->user_type == 'A';
    }

    public function update(AuthUser $authUser, User $user)
    {
        return $authUser->user_type == 'A' && $authUser->id == $user->id;
    }

    public function delete(AuthUser $authUser, User $user)
    {
        return $authUser->user_type == 'A';
    }

    public function updatePassword(AuthUser $authUser, User $user)
    {
        return $authUser->user_type == 'A' && $authUser->id == $user->id;
    }
}
