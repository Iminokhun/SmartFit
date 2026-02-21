<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Analytics;
use App\Filament\Pages\Analytics\Attendance;
use App\Filament\Resources\Activities\ActivityResource;
use App\Filament\Resources\ActivityCategories\ActivityCategoryResource;
use App\Filament\Resources\AssetEvents\AssetEventResource;
use App\Filament\Resources\CustomerSubscriptions\CustomerSubscriptionResource;
use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Halls\HallResource;
use App\Filament\Resources\Inventories\InventoryResource;
use App\Filament\Resources\InventoryMovements\InventoryMovementResource;
use App\Filament\Resources\Schedules\ScheduleResource;
use App\Filament\Resources\Shifts\ShiftResource;
use App\Filament\Resources\Subscriptions\SubscriptionResource;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
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
                InventoryResource::class,
                InventoryMovementResource::class,
                AssetEventResource::class,
            ])
            ->pages([
                Dashboard::class,
                Analytics::class,
                Attendance::class,
            ])
            ->plugins([
                FilamentApexChartsPlugin::make(),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
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
                Authenticate::class,
            ]);
    }
}

