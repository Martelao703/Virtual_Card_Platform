<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Resources\UserResource;
use App\Http\Resources\AdminResource;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Requests\UpdateUserPasswordRequest;
use App\Http\Resources\AuthUserResource;
use App\Models\AuthUser;

class AuthUserController extends Controller
{
    /*public function __construct()
    {
        $this->authorizeResource(AuthUser::class, 'authUser', ['except' => ['show_me']]);
    }*/

    public function index(Request $request)
    {
        $filterByType = $request->type ?? '';
        $filterByName = $request->name ?? '';

        $query = AuthUser::query();

        if ($filterByType !== "") {
            $query->where('user_type', '=', $filterByType);
        }

        if ($filterByName !== "") {
            $query->whereRaw('UPPER(name) LIKE ?', ['%' . strtoupper($filterByName) . '%']);
        }

        // Paginate the results
        $users = $query->paginate(20);

        return AuthUserResource::collection($users);
    }

    public function show(AuthUser $authUser)
    {
        return new AuthUserResource($authUser);
    }

    public function show_me(Request $request)
    {
        //$this->authorize('viewMe');
        return new AuthUserResource($request->user());
    }
}
