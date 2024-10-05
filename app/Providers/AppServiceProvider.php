<?php

namespace App\Providers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use App\Models\Vcard;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        Validator::extend('check_confirmation_code', function ($attribute, $value, $parameters, $validator) {
            $phone_number = $validator->getData()['phone_number']; 
            $hashedConfirmationCode = Vcard::where('phone_number', $phone_number)->value('confirmation_code');
            return password_verify($value, $hashedConfirmationCode);
        });
    }
}
