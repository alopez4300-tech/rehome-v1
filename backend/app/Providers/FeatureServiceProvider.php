<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class FeatureServiceProvider extends ServiceProvider
{
    /**
     * Register services conditionally based on feature flags.
     */
    public function register(): void
    {
        if (feature('cost_tracking')) {
            $this->app->bind(\App\Contracts\CostMeter::class, \App\Services\DatabaseCostMeter::class);
        } else {
            $this->app->bind(\App\Contracts\CostMeter::class, \App\Services\NullCostMeter::class);
        }

        if (feature('realtime')) {
            // Register realtime services when needed
            $this->app->register(\Laravel\Reverb\ReverbServiceProvider::class);
        }
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
