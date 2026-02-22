<?php

namespace App\Providers;

use App\Models\AssetEvent;
use App\Models\Activity;
use App\Models\Customer;
use App\Models\CustomerSubscription;
use App\Models\Expense;
use App\Models\Hall;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\Shift;
use App\Models\Subscription;
use App\Models\Visit;
use App\Observers\AssetEventObserver;
use App\Observers\InventoryMovementObserver;
use App\Observers\PaymentObserver;
use App\Policies\AdminDeleteCrudPolicy;
use App\Policies\ExpensePolicy;
use App\Policies\InventoryPolicy;
use App\Policies\PaymentPolicy;
use App\Services\Security\AuthEventLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

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
        Gate::policy(Activity::class, AdminDeleteCrudPolicy::class);
        Gate::policy(AssetEvent::class, AdminDeleteCrudPolicy::class);
        Gate::policy(Customer::class, AdminDeleteCrudPolicy::class);
        Gate::policy(CustomerSubscription::class, AdminDeleteCrudPolicy::class);
        Gate::policy(Hall::class, AdminDeleteCrudPolicy::class);
        Gate::policy(InventoryMovement::class, AdminDeleteCrudPolicy::class);
        Gate::policy(Schedule::class, AdminDeleteCrudPolicy::class);
        Gate::policy(Shift::class, AdminDeleteCrudPolicy::class);
        Gate::policy(Subscription::class, AdminDeleteCrudPolicy::class);
        Gate::policy(Visit::class, AdminDeleteCrudPolicy::class);

        Payment::observe(PaymentObserver::class);
        InventoryMovement::observe(InventoryMovementObserver::class);
        AssetEvent::observe(AssetEventObserver::class);

        Event::listen(Login::class, function (Login $event): void {
            if ($event->user instanceof \App\Models\User) {
                AuthEventLogger::logSuccess($event->user, (string) $event->guard);

                $request = request();
                if (strtolower((string) $request?->segment(1)) === 'manager') {
                    $key = Str::transliterate(Str::lower((string) $event->user->email) . '|' . $request?->ip() . '|manager');
                    app(RateLimiter::class)->clear($key);
                }
            }
        });

        Event::listen(Failed::class, function (Failed $event): void {
            $email = is_array($event->credentials) ? ($event->credentials['email'] ?? null) : null;
            AuthEventLogger::logFail($email, $event->guard);
        });
    }
}
