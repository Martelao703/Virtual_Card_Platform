<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class Vcard extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $table = 'vcards';
    protected $keyType = 'string';
    protected $primaryKey = 'phone_number';
    public $incrementing = false;
    public $timestamps = true;

    protected $fillable = [
        'phone_number',
        'name',
        'email',
        'photo_url',
        'password',
        'confirmation_code',
        'blocked',
        'balance',
        'max_debit',
        'custom_options',
        'custom_data',
    ];

    protected $hidden = [
        'password',
        'confirmation_code',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected $casts = [
        'password' => 'hashed',
        'confirmation_code' => 'hashed',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    public function categories()
    {
        return $this->hasMany(Category::class, 'vcard', 'phone_number');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'vcard', 'phone_number')->orderBy('datetime', 'desc');
    }

    public function findForPassport($username)
    {
        //return $this->where('username',$username)->first();
        return AuthUser::where('username', $username)->first();
    }
}
