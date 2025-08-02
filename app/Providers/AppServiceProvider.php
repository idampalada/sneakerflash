<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\RajaOngkirService;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Register RajaOngkir Service
        $this->app->singleton(RajaOngkirService::class, function ($app) {
            return new RajaOngkirService();
        });
    }

    public function boot()
    {
        //
    }
}