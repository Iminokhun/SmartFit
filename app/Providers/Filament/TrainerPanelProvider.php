<?php

namespace App\Providers\Filament;

use App\Filament\Pages\TrainerPersonal;
use App\Filament\Pages\TrainerSessionAttendance;
use App\Http\Middleware\AuthenticateFilamentPanel;
use App\Filament\Widgets\Trainer\TrainerTodaySessionsTable;
use App\Filament\Widgets\Trainer\TrainerTodayStats;
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

class TrainerPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('trainer')
            ->path('trainer')
            ->login()
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->brandLogo(asset('images/logo.svg'))
            ->brandLogoHeight('3.5rem')
            ->colors([
                'primary' => Color::Blue,
            ])
            ->pages([
                Dashboard::class,
                TrainerPersonal::class,
                TrainerSessionAttendance::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets/Trainer'), for: 'App\Filament\Widgets\Trainer')
            ->widgets([
                TrainerTodayStats::class,
                TrainerTodaySessionsTable::class,
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
            ->authMiddleware([
                AuthenticateFilamentPanel::class,
            ]);
    }
}
