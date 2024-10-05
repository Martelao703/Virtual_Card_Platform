<?php

namespace App\Policies;

use App\Models\AuthUser;
use App\Models\Category;
use Illuminate\Auth\Access\Response;

class CategoryPolicy
{
    public function viewAny(AuthUser $authUser)
    {
        return false;
    }

    public function view(AuthUser $authUser, Category $category)
    {
        return $authUser->id == $category->vcard;
    }

    public function create(AuthUser $authUser)
    {
        return $authUser->user_type == 'V';
    }

    public function update(AuthUser $authUser, Category $category)
    {
        return $authUser->id == $category->vcard;
    }

    public function delete(AuthUser $authUser, Category $category)
    {
        return $authUser->id == $category->vcard;
    }

    public function getTransactions(AuthUser $authUser, Category $category)
    {
        return $authUser->id == $category->vcard;
    }   
}
