<?php

namespace App\Providers;

use App\Filament\Responses\LoginResponse as AppLoginResponse;
use Filament\Http\Responses\Auth\Contracts\LoginResponse as FilamentLoginResponse;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind custom Filament login response for smart redirects
        $this->app->bind(FilamentLoginResponse::class, AppLoginResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
