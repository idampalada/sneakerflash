<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\RajaOngkirService;
use App\Models\Order;
use App\Observers\OrderObserver;

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
        // Register Order Observer untuk auto-sync user spending
        Order::observe(OrderObserver::class);
    }
}