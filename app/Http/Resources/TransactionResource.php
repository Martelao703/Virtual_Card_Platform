<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public static $format = 'default';
    public function toArray(Request $request): array
    {
        switch (TransactionResource::$format) {
            case 'default':
                return [
                    'id' => $this->id,
                    'vcard' => $this->vcard,
                    'date' => $this->date,
                    'datetime' => $this->datetime,
                    'type' => $this->type,
                    'value' => $this->value,
                    'old_balance' => $this->old_balance,
                    'new_balance' => $this->new_balance,
                    'payment_type' => $this->payment_type,
                    'payment_reference' => $this->payment_reference,
                    'pair_transaction' => $this->pair_transaction,
                    'pair_vcard' => $this->pair_vcard,
                    'category_id' => $this->category_id,
                    'description' => $this->description,
                    'custom_options' => $this->custom_options,
                    'custom_data' => $this->custom_data,
                ];
            case 'TAES':
                return [
                    'id' => $this->id,
                    'vcard' => $this->vcard,
                    'date' => $this->date,
                    'datetime' => $this->datetime,
                    'type' => $this->type,
                    'value' => $this->value,
                    'old_balance' => $this->old_balance,
                    'new_balance' => $this->new_balance,
                    'payment_type' => $this->payment_type,
                    'phone_number_destination' => $this->payment_reference,
                    'description' => $this->description,
                ];
            default:
                return [
                    #Not Defined
                ];
        }
        
    }
}
