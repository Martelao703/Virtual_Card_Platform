<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Resources\UserResource;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Requests\UpdateUserPasswordRequest;
use App\Http\Resources\VcardResource;

class UserController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(User::class, 'user', [
            'except' => ['update_password', 'show_me'],
        ]);
    }

    public function index()
    {
        return UserResource::collection(User::all());
    }

    public function show(User $user)
    {
        return new UserResource($user);
    }

    public function show_me(Request $request)
    {
        //$this->authorize('viewMe');
        return new UserResource($request->user());
    }

    public function store(StoreUserRequest $request)
    {
        $dataToSave = $request->validated();
        $user = new User();

        $user->name = $dataToSave['name'];
        $user->email = $dataToSave['email'];
        $user->password = bcrypt($dataToSave['password']);
        $user->custom_options = $dataToSave['custom_options'] ?? null;
        $user->custom_data = $dataToSave['custom_data'] ?? null;

        $user->save();
        return new UserResource($user);
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $dataToSave = $request->validated();

        $user->name = $dataToSave['name'] ?? $user->name;
        $user->email = $dataToSave['email'] ?? $user->email;
        $user->custom_options = $dataToSave['custom_options'] ?? $user->custom_options;
        $user->custom_data = $dataToSave['custom_data'] ?? $user->custom_data;

        $user->save();
        return new UserResource($user);
    }

    public function update_password(UpdateUserPasswordRequest $request, User $user)
    {
        //$this->authorize('update_password', $user);
        $user->password = bcrypt($request->validated()['password']);
        $user->save();
        return new UserResource($user);
    }

    public function destroy(User $user)
    {
        $user->forceDelete();
        return new UserResource($user);
    }
}
