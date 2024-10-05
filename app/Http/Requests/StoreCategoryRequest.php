<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
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
            'vcard' => 'required|string|digits:9|regex:/^9\d{8}$/|exists:vcards,phone_number',
            'type' => 'required|in:C,D',
            'name' => 'required|string|max:50',
            'custom_options' => 'nullable|json',
            'custom_data' => 'nullable|json',
        ];
    }
}
