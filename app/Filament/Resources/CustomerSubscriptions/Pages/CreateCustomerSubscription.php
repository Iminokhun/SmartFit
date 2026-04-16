<?php

namespace App\Filament\Resources\CustomerSubscriptions\Pages;

use App\Filament\Resources\CustomerSubscriptions\CustomerSubscriptionResource;
use App\Models\CustomerSubscription;
use App\Models\Payment;
use App\Models\Subscription;
use Carbon\Carbon;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateCustomerSubscription extends CreateRecord
{
    protected static string $resource = CustomerSubscriptionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->ensureRepurchaseRule($data);
        $this->applyAgreedPriceSnapshot($data);

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

    private function applyAgreedPriceSnapshot(array &$data): void
    {
        if (array_key_exists('agreed_price', $data) && $data['agreed_price'] !== null) {
            return;
        }

        $subscriptionId = (int) ($data['subscription_id'] ?? 0);
        if ($subscriptionId <= 0) {
            return;
        }

        $subscription = Subscription::query()->find($subscriptionId);
        if (! $subscription) {
            return;
        }

        $data['agreed_price'] = $subscription->finalPrice();
    }

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $paymentAmount = (float) ($data['payment_amount'] ?? 0);
            $paymentMethod = $data['payment_method'] ?? null;
            $paymentDescription = $data['payment_description'] ?? null;

            unset($data['payment_amount'], $data['payment_method'], $data['payment_description']);

            /** @var CustomerSubscription $customerSubscription */
            $customerSubscription = static::getModel()::create($data);

            if ($paymentAmount > 0 && $paymentMethod) {
                $finalPrice = $customerSubscription->finalPrice();

                Payment::query()->create([
                    'customer_id' => (int) $customerSubscription->customer_id,
                    'customer_subscription_id' => (int) $customerSubscription->id,
                    'amount' => $paymentAmount,
                    'method' => $paymentMethod,
                    'status' => $paymentAmount >= $finalPrice ? 'paid' : 'partial',
                    'description' => $paymentDescription ?: 'Initial payment',
                ]);
            }

            $customerSubscription->recalculatePaymentSummary();

            return $customerSubscription;
        });
    }

}
