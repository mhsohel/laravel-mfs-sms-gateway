<?php

namespace Prantiksoft\MfsSmsGateway;

use Illuminate\Support\ServiceProvider;
use Prantiksoft\MfsSmsGateway\Services\OrderMatcher;

class MfsSmsGatewayServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register()
    {
        // Merge package configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../config/mfs-sms.php',
            'mfs-sms'
        );

        // Bind OrderMatcher
        $this->app->singleton(OrderMatcher::class, function ($app) {
            return new OrderMatcher();
        });

        // Bind MfsSmsManager
        $this->app->singleton(MfsSmsManager::class, function ($app) {
            return new MfsSmsManager($app->make(OrderMatcher::class));
        });

        // Bind facade alias
        $this->app->alias(MfsSmsManager::class, 'mfs-sms');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        // 1. Load Package Routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');

        // 2. Load Package Migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // 3. Load Package Views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'mfs-sms-gateway');

        // 4. Publishing
        if ($this->app->runningInConsole()) {
            // Publish configuration
            $this->publishes([
                __DIR__ . '/../config/mfs-sms.php' => config_path('mfs-sms.php'),
            ], 'mfs-sms-config');

            // Publish migrations
            $this->publishes([
                __DIR__ . '/../database/migrations/' => database_path('migrations'),
            ], 'mfs-sms-migrations');

            // Publish views
            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/mfs-sms-gateway'),
            ], 'mfs-sms-views');
        }
    }
}
