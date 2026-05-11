<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        \App\Providers\ModuleProvider::register();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \App\Providers\ModuleProvider::boot();
    }
}
