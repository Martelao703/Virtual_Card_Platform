<?php

namespace App\Providers;

use App\Models\AuthUser;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        'App\Models\Vcard' => 'App\Policies\VcardPolicy',
        'App\Models\Transaction' => 'App\Policies\TransactionPolicy',
        'App\Models\Category' => 'App\Policies\CategoryPolicy',
        'App\Models\DefaultCategory' => 'App\Policies\DefaultCategoryPolicy',
        'App\Models\User' => 'App\Policies\UserPolicy',
        'App\Models\AuthUser' => 'App\Policies\AuthUserPolicy'
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //$this->registerPolicies();

        Gate::define('admin', function (AuthUser $user) {
            return $user->user_type === 'A';
        });
    }
}
