<?php

namespace App\Http\Requests;

use App\Models\Vcard;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;

class StoreTransactionRequest extends FormRequest
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

        $phone_number = Auth::user()->username;
        $vcard = Vcard::where('phone_number', $phone_number)->first();

        return Auth::check() && Hash::check($confirmation_code, $vcard->confirmation_code);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $paymentType = $this->input('payment_type');
        $user = $this->user();

        $paymentReferenceRules = [
            'VCARD' => 'string|digits:9|regex:/^9\d{8}$/|exists:vcards,phone_number',
            'MBWAY' => 'string|digits:9|regex:/^9\d{8}$/',
            'PAYPAL' => 'string|email',
            'IBAN' => 'string|regex:/^[A-Za-z]{2}\d{23}$/',
            'MB' => 'string|regex:/^\d{5}-\d{9}$/',
            'VISA' => 'string|regex:/^4\d{15}$/',
        ];

        $confirmationCodeRule = $user && $user->user_type == 'V' ? 'required' : 'prohibited';

        return [
            'id' => 'prohibited',
            'vcard' => 'required|string|digits:9|regex:/^9\d{8}$/|exists:vcards,phone_number',
            'type' => 'prohibited',
            'value' => 'required|numeric|min:0.01',
            'payment_type' => 'required|in:VCARD,MBWAY,PAYPAL,IBAN,MB,VISA',
            'payment_reference' => [
                'required',
                function ($attribute, $value, $fail) use ($paymentType, $paymentReferenceRules) {
                    $rule = $paymentReferenceRules[$paymentType] ?? null;

                    if (!$rule) {
                        $fail('Invalid payment type');
                        return;
                    }

                    $validator = validator([$attribute => $value], [$attribute => $rule]);

                    if ($validator->fails()) {
                        $fail($validator->errors()->first($attribute));
                    }
                },
            ],
            'category_id' => 'prohibited',
            'description' => 'prohibited',
            'custom_options' => 'nullable|json',
            'custom_data' => 'nullable|json',
            'confirmation_code' => Gate::denies('admin') ? 'required|string' : '',
        ];
    }
}
