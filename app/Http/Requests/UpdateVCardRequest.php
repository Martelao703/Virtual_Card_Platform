<?php

namespace App\Http\Requests;

use App\Services\Base64Services;
use Illuminate\Foundation\Http\FormRequest;

class UpdateVCardRequest extends FormRequest
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
            'phone_number' => 'prohibited',
            'name' => 'string|max:255',
            'email' => 'email|max:255',
            'photo_url' => 'nullable|string|max:255',
            'password' => 'string|max:255',
            'confirmation_code' => 'string|size:3',
            'blocked' => 'boolean',
            'balance' => 'prohibited',
            'max_debit' => 'numeric|min:0',
            'custom_options' => 'nullable|json',
            'custom_data' => 'nullable|json',
            'base64ImagePhoto' => 'nullable|string',
            'deletePhotoOnServer' => 'nullable|boolean'
        ];
    }
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $base64ImagePhoto = $this->base64ImagePhoto ?? null;
            if ($base64ImagePhoto) {
                $base64Service = new Base64Services();
                $mimeType = $base64Service->mimeType($base64ImagePhoto);
                if (!in_array($mimeType, ['image/png', 'image/jpg', 'image/jpeg'])) {
                    $validator->errors()->add('base64ImagePhoto', 'File type not supported (only supports "png" and "jpeg" images).');
                }
            }
        });
    }
}
