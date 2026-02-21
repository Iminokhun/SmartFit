<?php

namespace App\Providers;

use App\Models\AssetEvent;
use App\Models\Expense;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Payment;
use App\Observers\AssetEventObserver;
use App\Observers\InventoryMovementObserver;
use App\Observers\PaymentObserver;
use App\Policies\ExpensePolicy;
use App\Policies\InventoryPolicy;
use App\Policies\PaymentPolicy;
use Illuminate\Support\Facades\Gate;
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
        Gate::policy(Payment::class, PaymentPolicy::class);
        Gate::policy(Inventory::class, InventoryPolicy::class);
        Gate::policy(Expense::class, ExpensePolicy::class);

        Payment::observe(PaymentObserver::class);
        InventoryMovement::observe(InventoryMovementObserver::class);
        AssetEvent::observe(AssetEventObserver::class);
    }
}
