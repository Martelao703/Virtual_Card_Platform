<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VcardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public static $format = 'default';
    public function toArray(Request $request): array
    {
        switch (VcardResource::$format) {
            case 'default':
                return [
                    'phone_number' => $this->phone_number,
                    'name' => $this->name,
                    'email' => $this->email,
                    'photo_url' => $this->photo_url,
                    'blocked' => $this->blocked,
                    'balance' => $this->balance,
                    'max_debit' => $this->max_debit,
                    'custom_options' => $this->custom_options,
                    'custom_data' => $this->custom_data
                ];
            case 'TAES':
                return [
                    'phone_number' => $this->phone_number,
                    'balance' => $this->balance,
                    'custom_data' => json_decode($this->custom_data, true),
                ];
            default:
                return [
                    #Not Defined
                ];


        }

    }
}
