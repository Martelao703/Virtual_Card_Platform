<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class DefaultCategory extends Model
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;
    public $timestamps = false;

    protected $fillable = [
        'type',
        'name',
        'custom_options',
        'custom_data',
    ];

    protected $hidden = ['deleted_at'];

    protected $casts = ['deleted_at' => 'datetime'];
}
