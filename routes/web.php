<?php

use App\Http\Controllers\TelegramMiniAppController;
use App\Http\Controllers\TelegramStaffMiniAppController;
use App\Http\Controllers\TelegramStaffWebhookController;
use App\Http\Controllers\TelegramWebhookController;
use App\Http\Controllers\CheckinQrController;
use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use App\Livewire\Settings\TwoFactor;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('/staff', 'auth.staff-login')->name('staff.login');
Route::view('/staff/login', 'auth.staff-login');

Route::post('/telegram/webhook', TelegramWebhookController::class)
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->name('telegram.webhook');

Route::post('/telegram/staff/webhook', TelegramStaffWebhookController::class)
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->name('telegram.staff.webhook');

Route::middleware(['force.telegram.https'])->prefix('telegram')->group(function () {
    Route::get('/mini-app', [TelegramMiniAppController::class, 'show'])
        ->name('telegram.mini-app.show');

    Route::get('/mini-app/subscriptions', [TelegramMiniAppController::class, 'subscriptions'])
        ->name('telegram.mini-app.subscriptions');

    Route::post('/mini-app/link', [TelegramMiniAppController::class, 'link'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
        ->name('telegram.mini-app.link');

    Route::post('/mini-app/me', [TelegramMiniAppController::class, 'me'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
        ->name('telegram.mini-app.me');

    Route::post('/mini-app/catalog', [TelegramMiniAppController::class, 'catalog'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
        ->name('telegram.mini-app.catalog');

    Route::post('/mini-app/purchase/invoice', [TelegramMiniAppController::class, 'purchaseInvoice'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
        ->name('telegram.mini-app.purchase.invoice');

    Route::post('/mini-app/checkin-qr', [TelegramMiniAppController::class, 'checkinQr'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
        ->name('telegram.mini-app.checkin-qr');

    Route::post('/mini-app/checkin-qr/status', [TelegramMiniAppController::class, 'checkinQrStatus'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
        ->name('telegram.mini-app.checkin-qr.status');

    Route::get('/mini-app/qr', [TelegramMiniAppController::class, 'qrPage'])
        ->name('telegram.mini-app.qr');

    Route::get('/mini-app/my-subscriptions', [TelegramMiniAppController::class, 'mySubscriptionsPage'])
        ->name('telegram.mini-app.my-subscriptions');

    Route::post('/mini-app/my-subscriptions', [TelegramMiniAppController::class, 'mySubscriptions'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
        ->name('telegram.mini-app.my-subscriptions.data');

    Route::post('/mini-app/my-visits', [TelegramMiniAppController::class, 'myVisits'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
        ->name('telegram.mini-app.my-visits');

    Route::get('/mini-app/chat', [TelegramMiniAppController::class, 'chatPage'])
        ->name('telegram.mini-app.chat');

    Route::post('/mini-app/chat', [TelegramMiniAppController::class, 'chat'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
        ->name('telegram.mini-app.chat.send');

    Route::post('/mini-app/chat/photo', [TelegramMiniAppController::class, 'chatPhoto'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
        ->name('telegram.mini-app.chat.photo');

    Route::get('/staff/scan', [TelegramStaffMiniAppController::class, 'show'])
        ->name('telegram.staff.scan.show');

    Route::post('/staff/scan/me', [TelegramStaffMiniAppController::class, 'me'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
        ->name('telegram.staff.scan.me');

    Route::post('/staff/scan/link', [TelegramStaffMiniAppController::class, 'link'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
        ->name('telegram.staff.scan.link');

    Route::post('/staff/scan/resolve', [TelegramStaffMiniAppController::class, 'resolve'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
        ->name('telegram.staff.scan.resolve');

    Route::post('/staff/scan/consume', [TelegramStaffMiniAppController::class, 'consume'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
        ->name('telegram.staff.scan.consume');
});

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', Profile::class)->name('profile.edit');
    Route::get('settings/password', Password::class)->name('user-password.edit');
    Route::get('settings/appearance', Appearance::class)->name('appearance.edit');

    Route::get('settings/two-factor', TwoFactor::class)
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');

    Route::post('/checkin/scan/resolve', [CheckinQrController::class, 'resolve'])
        ->name('checkin.scan.resolve');
    Route::post('/checkin/scan/consume', [CheckinQrController::class, 'consume'])
        ->name('checkin.scan.consume');
});

