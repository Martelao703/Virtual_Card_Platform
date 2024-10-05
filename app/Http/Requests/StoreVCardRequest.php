<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVCardRequest extends FormRequest
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
            'phone_number' => 'required|string|size:9|regex:/^9\d{8}$/|unique:vcards,phone_number',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'photo_url' => 'sometimes|nullable|string|max:255',
            'password' => 'required|string|max:255|size:8',
            'password_confirmation'=>'required|string|max:255|same:password',
            'confirmation_code' => 'required|string|size:4',
            'blocked' => 'prohibited',
            'balance' => 'prohibited',
            'max_debit' => 'prohibited',
            'custom_options' => 'nullable|json',
            'custom_data' => 'nullable|json',
            'base64ImagePhoto' => 'nullable|string'
        ];
    }
}
