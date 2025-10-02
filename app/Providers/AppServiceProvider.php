<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Sale;
use App\Models\Purchase;
use App\Observers\SaleObserver;
use App\Observers\PurchaseObserver;

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
        // Register observers
        Sale::observe(SaleObserver::class);
        Purchase::observe(PurchaseObserver::class);
    }
}
