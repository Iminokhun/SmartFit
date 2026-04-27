<?php

namespace App\Http\Controllers;

use App\Models\TelegramStaffLink;
use App\Models\User;
use App\Services\Checkin\QrCheckinService;
use App\Services\Telegram\TelegramAuthService;
use App\Services\Telegram\TelegramBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class TelegramStaffMiniAppController extends Controller
{
    public function __construct(private TelegramAuthService $auth) {}

    public function show()
    {
        return view('telegram.staff-scan');
    }

    public function me(Request $request): JsonResponse
    {
        $data = $request->validate([
            'init_data' => ['required', 'string'],
        ]);

        $telegramUser = $this->auth->validateAndExtract($data['init_data'], (string) config('services.telegram_staff.bot_token'));
        if (! $telegramUser) {
            return response()->json([
                'ok' => false,
                'message' => 'Invalid Telegram session.',
            ], 422);
        }

        $telegramUserId = (int) ($telegramUser['id'] ?? 0);
        $link = TelegramStaffLink::query()
            ->where('telegram_user_id', $telegramUserId)
            ->with('user.role', 'user.staff')
            ->first();

        if (! $link || ! $link->user) {
            return response()->json([
                'ok' => true,
                'linked' => false,
            ]);
        }

        if (! $this->canScanByUser($link->user)) {
            return response()->json([
                'ok' => false,
                'linked' => true,
                'message' => 'Role is not allowed to scan QR.',
            ], 403);
        }

        return response()->json([
            'ok' => true,
            'linked' => true,
            'staff' => [
                'user_id' => (int) $link->user->id,
                'name' => (string) ($link->user->name ?? 'Staff'),
                'role' => (string) ($link->user->role?->name ?? '-'),
            ],
        ]);
    }

    public function link(Request $request): JsonResponse
    {
        $data = $request->validate([
            'init_data' => ['required', 'string'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        $telegramUser = $this->auth->validateAndExtract($data['init_data'], (string) config('services.telegram_staff.bot_token'));
        if (! $telegramUser) {
            return response()->json([
                'ok' => false,
                'message' => 'Invalid Telegram session.',
            ], 422);
        }

        $telegramUserId = (int) ($telegramUser['id'] ?? 0);
        if ($telegramUserId <= 0) {
            return response()->json([
                'ok' => false,
                'message' => 'Telegram user not found.',
            ], 422);
        }

        $user = User::query()
            ->with('role', 'staff')
            ->where('email', strtolower((string) $data['email']))
            ->first();

        if (! $user || ! Hash::check((string) $data['password'], (string) $user->password)) {
            return response()->json([
                'ok' => false,
                'message' => 'Invalid email or password.',
            ], 422);
        }

        if (! $this->canScanByUser($user)) {
            return response()->json([
                'ok' => false,
                'message' => 'Role is not allowed to use scanner.',
            ], 403);
        }

        $existingByTelegram = TelegramStaffLink::query()
            ->where('telegram_user_id', $telegramUserId)
            ->first();
        if ($existingByTelegram && (int) $existingByTelegram->user_id !== (int) $user->id) {
            return response()->json([
                'ok' => false,
                'message' => 'This Telegram account is already linked to another staff user.',
            ], 422);
        }

        TelegramStaffLink::updateOrCreate(
            ['user_id' => (int) $user->id],
            [
                'telegram_user_id' => $telegramUserId,
                'telegram_username' => $telegramUser['username'] ?? null,
                'first_name' => $telegramUser['first_name'] ?? null,
                'last_name' => $telegramUser['last_name'] ?? null,
                'is_verified' => true,
                'linked_at' => now(),
            ]
        );

        return response()->json([
            'ok' => true,
            'message' => 'Staff account linked successfully.',
            'staff' => [
                'user_id' => (int) $user->id,
                'name' => (string) ($user->name ?? 'Staff'),
                'role' => (string) ($user->role?->name ?? '-'),
            ],
        ]);
    }

    public function resolve(Request $request, QrCheckinService $checkinService): JsonResponse
    {
        $data = $request->validate([
            'init_data' => ['required', 'string'],
            'qr_payload' => ['required', 'string'],
            'schedule_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $user = $this->resolveScannerUser($data['init_data']);
        if (! $user) {
            return response()->json([
                'ok' => false,
                'message' => 'Forbidden.',
            ], 403);
        }

        $result = $checkinService->resolveOrConsume(
            $data['qr_payload'],
            (int) $user->id,
            $data['schedule_id'] ?? null
        );

        \Log::info('staff.scan.resolve.payload', $request->all());

        if (($result['ok'] ?? false) && ! ($result['requires_selection'] ?? false)) {
            $this->notifyStaff($user, $result);
        }

        return $this->jsonResponse($result);
    }

    public function consume(Request $request, QrCheckinService $checkinService): JsonResponse
    {
        $data = $request->validate([
            'init_data' => ['required', 'string'],
            'qr_payload' => ['required', 'string'],
            'customer_subscription_id' => ['required', 'integer', 'min:1'],
            'schedule_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $user = $this->resolveScannerUser($data['init_data']);
        if (! $user) {
            return response()->json([
                'ok' => false,
                'message' => 'Forbidden.',
            ], 403);
        }

        $result = $checkinService->consume(
            $data['qr_payload'],
            (int) $data['customer_subscription_id'],
            (int) $user->id,
            $data['schedule_id'] ?? null
        );
        \Log::info('staff.scan.consume.payload', $request->all());

        if ($result['ok'] ?? false) {
            $this->notifyStaff($user, $result);
        }

        return $this->jsonResponse($result);
    }

    private function resolveScannerUser(string $initData): ?User
    {
        $telegramUser = $this->auth->validateAndExtract($initData, (string) config('services.telegram_staff.bot_token'));
        if (! $telegramUser) {
            return null;
        }

        $telegramUserId = (int) ($telegramUser['id'] ?? 0);
        if ($telegramUserId <= 0) {
            return null;
        }

        $link = TelegramStaffLink::query()
            ->where('telegram_user_id', $telegramUserId)
            ->with('user.role', 'user.staff')
            ->first();

        if (! $link || ! $link->user) {
            return null;
        }

        return $this->canScanByUser($link->user) ? $link->user : null;
    }

    private function canScanByUser(User $user): bool
    {
        if ($user->staff && strtolower((string) $user->staff->status) === 'inactive') {
            return false;
        }

        $roleName = strtolower((string) ($user->role?->name ?? ''));
        if (in_array($roleName, ['admin', 'manager'], true)) {
            return true;
        }

        return in_array((int) $user->role_id, [1, 6], true);
    }

    private function notifyStaff(User $user, array $result): void
    {
        $link = TelegramStaffLink::where('user_id', $user->id)->value('telegram_user_id');
        if (! $link) {
            return;
        }

        $sub = $result['subscription'] ?? [];
        $visits = $sub['remaining_visits_label'] ?? '—';
        $text = "✅ Check-in registered\n\n"
            . "👤 Customer: " . ($result['customer_name'] ?? '—') . "\n"
            . "📋 Subscription: " . ($sub['subscription_name'] ?? '—') . "\n"
            . "🔢 Remaining: {$visits}";

        app(TelegramBotService::class)->sendStaffMessage($link, $text);
    }

    private function jsonResponse(array $result): JsonResponse
    {
        $status = (int) ($result['status'] ?? 200);
        unset($result['status']);

        return response()->json($result, $status);
    }
}
