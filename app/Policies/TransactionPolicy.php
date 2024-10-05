<?php

namespace App\Policies;

use App\Models\AuthUser;
use App\Models\Transaction;

class TransactionPolicy
{
    public function viewAny(AuthUser $authUser)
    {
        return false;
    }

    public function view(AuthUser $authUser, Transaction $transaction)
    {
        return $authUser->id == $transaction->vcard;
    }

    public function create(AuthUser $authUser)
    {
        return true;
        //return $authUser->exists();
    }

    public function update(AuthUser $authUser, Transaction $transaction)
    {
        return $authUser->id == $transaction->vcard;
    }

    public function delete(AuthUser $authUser, Transaction $transaction)
    {
        return $authUser->id == $transaction->vcard;
    }
}
