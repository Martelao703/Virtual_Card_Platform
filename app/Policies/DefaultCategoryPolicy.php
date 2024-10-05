<?php

namespace App\Policies;

use App\Models\AuthUser;

class DefaultCategoryPolicy
{
    public function viewAny(AuthUser $authUser)
    {
        return $authUser->user_type == 'A';
    }

    public function view(AuthUser $authUser)
    {
        return $authUser->user_type == 'A';
    }

    public function create(AuthUser $authUser)
    {
        return $authUser->user_type == 'A';
    }

    public function update(AuthUser $authUser)
    {
        return $authUser->user_type == 'A';
    }

    public function delete(AuthUser $authUser)
    {
        return $authUser->user_type == 'A';
    }
}
