<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;
    public $timestamps = true;

    protected $fillable = [
        'vcard', 'date', 'datetime', 'type', 'value', 'old_balance', 'new_balance',
        'payment_type', 'payment_reference', 'pair_transaction', 'pair_vcard', 'category_id', 'description',
        'custom_options', 'custom_data'
    ];

    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    public function vcard()
    {
        return $this->belongsTo(Vcard::class, 'vcard', 'phone_number');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function pairTransaction()
    {
        return $this->belongsTo(Transaction::class, 'pair_transaction');
    }

    public function pairVCard()
    {
        return $this->belongsTo(VCard::class, 'pair_vcard', 'phone_number');
    }
}
