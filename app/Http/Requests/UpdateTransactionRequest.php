<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id' => 'prohibited',
            'vcard' => 'prohibited',
            'date' => 'prohibited',
            'datetime' => 'prohibited',
            'type' => 'prohibited',
            'value' => 'prohibited',
            'payment_type' => 'prohibited',
            'payment_reference' => 'prohibited',
            'pair_transaction' => 'prohibited',
            'pair_vcard' => 'prohibited',
            'category_id' => 'nullable|integer|exists:categories,id',
            'description' => 'nullable|string',
            'custom_options' => 'nullable|json',
            'custom_data' => 'nullable|json',
        ];
    }
}
