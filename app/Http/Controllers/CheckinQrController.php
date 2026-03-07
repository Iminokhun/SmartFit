<?php

namespace App\Http\Controllers;

use App\Services\Checkin\QrCheckinService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckinQrController extends Controller
{
    public function resolve(Request $request, QrCheckinService $checkinService): JsonResponse
    {
        if (! $this->canScan($request)) {
            return response()->json([
                'ok' => false,
                'message' => 'Forbidden.',
            ], 403);
        }

        $data = $request->validate([
            'qr_payload' => ['required', 'string'],
        ]);

        $result = $checkinService->resolveOrConsume(
            $data['qr_payload'],
            $request->user()?->id,
        );

        $status = (int) ($result['status'] ?? 200);
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function consume(Request $request, QrCheckinService $checkinService): JsonResponse
    {
        if (! $this->canScan($request)) {
            return response()->json([
                'ok' => false,
                'message' => 'Forbidden.',
            ], 403);
        }

        $data = $request->validate([
            'qr_payload' => ['required', 'string'],
            'customer_subscription_id' => ['required', 'integer', 'min:1'],
        ]);

        $result = $checkinService->consume(
            $data['qr_payload'],
            (int) $data['customer_subscription_id'],
            $request->user()?->id,
        );

        $status = (int) ($result['status'] ?? 200);
        unset($result['status']);

        return response()->json($result, $status);
    }

    private function canScan(Request $request): bool
    {
        $user = $request->user();
        if (! $user) {
            return false;
        }

        $roleName = strtolower((string) ($user->role?->name ?? ''));
        if (in_array($roleName, ['admin', 'manager', 'trainer'], true)) {
            return true;
        }

        return in_array((int) $user->role_id, [1, 6], true);
    }
}

