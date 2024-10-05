<?php

namespace App\Http\Requests;

use App\Models\AuthUser;
use App\Models\Vcard;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Gate;

class DestroyVcardRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if (Gate::allows('admin')) {
            return true;
        }

        $confirmation_code = $this->input('confirmation_code');
        $password = $this->input('password');

        $phone_number = Auth::user()->username;
        $vcard = Vcard::where('phone_number', $phone_number)->first();

        return Auth::check() && Hash::check($password, Auth::user()->password) && Hash::check($confirmation_code, $vcard->confirmation_code);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return Gate::denies('admin') ? [
            'confirmation_code' => 'required|string',
            'password' => 'required|string',
        ] : [];
    }
}
