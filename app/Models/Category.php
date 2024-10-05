<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory;
    use SoftDeletes;
    public $timestamps = false;
    protected $fillable = ['vcard', 'type', 'name', 'custom_options', 'custom_data'];

    protected $hidden = ['deleted_at'];

    protected $casts = ['deleted_at' => 'datetime'];

    public function vcard()
    {
        return $this->belongsTo(Vcard::class, 'vcard', 'phone_number');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class)->orderBy('datetime', 'desc');
    }
}
