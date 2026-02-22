<?php

namespace App\Providers\Filament;

use App\Http\Middleware\AuthenticateFilamentPanel;
use App\Http\Middleware\ManagerLoginRateLimit;
use App\Filament\Pages\ManagerPersonal;
use App\Filament\Resources\Activities\ActivityResource;
use App\Filament\Resources\ActivityCategories\ActivityCategoryResource;
use App\Filament\Resources\AssetEvents\AssetEventResource;
use App\Filament\Resources\CustomerSubscriptions\CustomerSubscriptionResource;
use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\ExpenseCategories\ExpenseCategoryResource;
use App\Filament\Resources\Expenses\ExpenseResource;
use App\Filament\Resources\Halls\HallResource;
use App\Filament\Resources\Inventories\InventoryResource;
use App\Filament\Resources\InventoryMovements\InventoryMovementResource;
use App\Filament\Resources\Payments\PaymentResource;
use App\Filament\Resources\Schedules\ScheduleResource;
use App\Filament\Resources\Shifts\ShiftResource;
use App\Filament\Resources\Subscriptions\SubscriptionResource;
use App\Filament\Widgets\Manager\ManagerLowStockTable;
use App\Filament\Widgets\Manager\ManagerOperationsStats;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Leandrocfe\FilamentApexCharts\FilamentApexChartsPlugin;

class ManagerPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('manager')
            ->path('manager')
            ->login()
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->colors([
                'primary' => Color::Amber,
            ])

            ->resources([
                ScheduleResource::class,
                HallResource::class,
                ShiftResource::class,
                CustomerResource::class,
                CustomerSubscriptionResource::class,
                SubscriptionResource::class,
                ActivityResource::class,
                ActivityCategoryResource::class,
                PaymentResource::class,
                InventoryResource::class,
                InventoryMovementResource::class,
                AssetEventResource::class,
                ExpenseResource::class,
            ])
            ->pages([
                Dashboard::class,
                ManagerPersonal::class,
            ])
            ->plugins([
                FilamentApexChartsPlugin::make(),
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets/Manager'), for: 'App\Filament\Widgets\Manager')
            ->widgets([
                ManagerOperationsStats::class,
                ManagerLowStockTable::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                ManagerLoginRateLimit::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->resourceCreatePageRedirect('index')
            ->resourceEditPageRedirect('index')
            ->authMiddleware([
                AuthenticateFilamentPanel::class,
            ]);
    }
}
