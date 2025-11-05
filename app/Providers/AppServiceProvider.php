<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\EgoSmsService;
use App\Services\RentReminderService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
       $this->app->singleton(EgoSmsService::class, function ($app) {  // ← CORRECT
        return new EgoSmsService();
        });
        
        $this->app->singleton(RentReminderService::class, function ($app) {
            return new RentReminderService($app->make(EgoSmsService::class));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
