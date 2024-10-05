<?php

namespace App\Policies;

use App\Models\AuthUser;
use App\Models\Vcard;

class VcardPolicy
{
    public function viewAny(AuthUser $authUser)
    {
        return $authUser->user_type == 'A';
    }

    public function view(AuthUser $authUser, Vcard $vcard)
    {
        return (string)$authUser->id == (string)$vcard->phone_number || $authUser->user_type == 'A';
    }

    public function viewMe(AuthUser $authUser)
    {
        return true;
        //return $authUser->exists();
    }

    public function viewExtraInfo(AuthUser $authUser)
    {
        return $authUser->user_type == 'A';
    }

    public function viewCategories(AuthUser $authUser, Vcard $vcard)
    {
        return $authUser->id == $vcard->phone_number;
    }

    public function viewTransactions(AuthUser $authUser, Vcard $vcard)
    {
        return $authUser->id == $vcard->phone_number;
    }

    public function create(AuthUser $authUser)
    {
        return true;
        //return $authUser->exists();
    }

    public function update(AuthUser $authUser, Vcard $vcard)
    {
        return $authUser->id == $vcard->phone_number || $authUser->user_type == 'A';
    }

    public function updatePassword(AuthUser $authUser, Vcard $vcard)
    {
        return $authUser->id == $vcard->phone_number;
    }

    public function updateConfirmationCode(AuthUser $authUser, Vcard $vcard)
    {
        return $authUser->id == $vcard->phone_number;
    }

    public function delete(AuthUser $authUser, Vcard $vcard)
    {
        return $authUser->id == $vcard->phone_number || $authUser->user_type == 'A';
    }
}
