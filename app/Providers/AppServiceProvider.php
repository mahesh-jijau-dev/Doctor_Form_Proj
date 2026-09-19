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
        Gate::policy(\App\Models\Form::class, \App\Policies\FormPolicy::class);
        Gate::policy(\App\Models\FormResponse::class, \App\Policies\ResponsePolicy::class);
        Gate::policy(\App\Models\Doctor::class, \App\Policies\DoctorPolicy::class);
    }
}
