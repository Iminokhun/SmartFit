<?php

namespace App\Providers;

use App\Models\AssetEvent;
use App\Models\InventoryMovement;
use App\Models\Payment;
use App\Observers\AssetEventObserver;
use App\Observers\InventoryMovementObserver;
use App\Observers\PaymentObserver;
use Illuminate\Support\ServiceProvider;

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
        Payment::observe(PaymentObserver::class);
        InventoryMovement::observe(InventoryMovementObserver::class);
        AssetEvent::observe(AssetEventObserver::class);
    }
}
