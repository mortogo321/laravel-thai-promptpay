<?php

namespace Mortogo321\LaravelThaiPromptPay;

use Illuminate\Support\ServiceProvider;

class PromptPayServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/promptpay.php',
            'promptpay'
        );

        $this->app->singleton('promptpay', function ($app) {
            return new PromptPayQR();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/promptpay.php' => config_path('promptpay.php'),
            ], 'promptpay-config');

            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/promptpay'),
            ], 'promptpay-views');
        }
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return ['promptpay'];
    }
}
