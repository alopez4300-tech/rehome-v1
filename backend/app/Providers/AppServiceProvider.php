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

        // Gate heavy providers - only load in scale profile
        $scale = env('APP_PROFILE', 'light') === 'scale';
        if ($scale) {
            // Register heavy stuff when needed:
            // $this->app->register(\Laravel\Horizon\HorizonServiceProvider::class);
            // $this->app->register(\App\Providers\RealtimeServiceProvider::class);
            // $this->app->register(\App\Providers\AnalyticsServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
