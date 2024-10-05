<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVcardConfirmationCodeRequest extends FormRequest
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
            'current_confirmation_code' => 'check_confirmation_code',
            'code'=> 'required|size:4',
            'confirmation_code' => 'required|same:code',
            'phone_number' => 'required'
        ];
    }
    public function messages()
    {
        return [
            'check_confirmation_code' => 'The confirmation code does not match.',
        ];
    }
}
