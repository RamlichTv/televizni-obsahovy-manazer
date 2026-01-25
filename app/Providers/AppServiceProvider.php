<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

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
    public function boot(): void
    {
        Gate::before(function ($user, $permission) {
            return $user->hasPermission($permission) ? true : null;
        });

        Gate::policy(\App\Models\ContentObject::class, \App\Policies\ContentObjectPolicy::class);
        Gate::policy(\App\Models\Module::class, \App\Policies\ModulePolicy::class);
        Gate::policy(\App\Models\Profile::class, \App\Policies\ProfilePolicy::class);
    }
}
