<?php

namespace App\Filament\Resources\CustomerSubscriptions\Pages;

use App\Filament\Resources\CustomerSubscriptions\CustomerSubscriptionResource;
use App\Models\CustomerSubscription;
use Carbon\Carbon;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditCustomerSubscription extends EditRecord
{
    protected static string $resource = CustomerSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->visible(fn () => auth()->user()?->can('delete', $this->record)),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->ensureRepurchaseRule($data);

        $status = $data['status'] ?? null;
        if (in_array($status, ['cancelled', 'frozen', 'pending'], true)) {
            return $data;
        }

        $endDate = $data['end_date'] ?? null;
        $remaining = $data['remaining_visits'] ?? null;

        $isExpired = $endDate && Carbon::parse($endDate)->lt(Carbon::today());
        $noVisits = ($remaining !== null) && ((int) $remaining <= 0);

        if ($isExpired || $noVisits) {
            $data['status'] = 'expired';
        }

        return $data;
    }

    private function ensureRepurchaseRule(array $data): void
    {
        $customerId = (int) ($data['customer_id'] ?? 0);
        $subscriptionId = (int) ($data['subscription_id'] ?? 0);

        if ($customerId <= 0 || $subscriptionId <= 0) {
            return;
        }

        $activeSamePlan = CustomerSubscription::query()
            ->where('customer_id', $customerId)
            ->where('subscription_id', $subscriptionId)
            ->where('status', 'active')
            ->whereDate('start_date', '<=', Carbon::today()->toDateString())
            ->whereDate('end_date', '>=', Carbon::today()->toDateString())
            ->whereKeyNot($this->record->getKey())
            ->orderByDesc('id')
            ->first();

        if (! $activeSamePlan) {
            return;
        }

        if ($activeSamePlan->remaining_visits === null) {
            throw ValidationException::withMessages([
                'subscription_id' => 'This unlimited plan is active now. You can add it after expiry.',
            ]);
        }

        if ((int) $activeSamePlan->remaining_visits > 1) {
            throw ValidationException::withMessages([
                'subscription_id' => 'You can add the same plan only when 1 visit is left.',
            ]);
        }
    }
}
