<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class AuthUser extends Authenticatable
{
    use HasFactory, SoftDeletes, HasApiTokens, Notifiable;
    protected $table = 'view_auth_users';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'user_type',
        'username',
        'password',
        'confirmation_code',
        'name',
        'email',
        'blocked',
        'photo_url'
    ];

    protected $hidden = [
        'password',
        'confirmation_code',
        'deleted_at'
    ];


    protected $casts = [
        'password' => 'hashed',
        'confirmation_code' => 'hashed',
        'deleted_at' => 'datetime'
    ];

    public function findForPassport($username){
        return $this->where('username',$username)->first();
    }
}
