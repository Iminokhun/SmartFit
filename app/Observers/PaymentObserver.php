<?php

namespace App\Observers;

use App\Models\Payment;
use App\Models\CustomerSubscription;

class PaymentObserver
{
    public function saving(Payment $payment): void
    {
        if ($payment->isDirty('status') && in_array($payment->status, ['pending', 'failed'], true)) {
            return;
        }

        $subscription = $payment->customerSubscription?->subscription;
        if (! $subscription || $payment->amount === null) {
            return;
        }

        $price = $subscription->finalPrice();
        $amount = (float) $payment->amount;
        if ($price <= 0 || $amount <= 0) {
            return;
        }

        $price = round($price, 2);
        $half = round($price / 2, 2);
        $amount = round($amount, 2);

        if ($amount === $price) {
            $payment->status = 'paid';
            return;
        }

        if ($amount === $half) {
            $payment->status = 'partial';
        }
    }

    public function saved(Payment $payment): void
    {
        $this->recalculateSubscription($payment->customer_subscription_id);

        if ($payment->wasChanged('customer_subscription_id')) {
            $this->recalculateSubscription($payment->getOriginal('customer_subscription_id'));
        }
    }

    public function deleted(Payment $payment): void
    {
        $this->recalculateSubscription($payment->customer_subscription_id);
    }

    private function recalculateSubscription(?int $subscriptionId): void
    {
        if (! $subscriptionId) {
            return;
        }

        $subscription = CustomerSubscription::find($subscriptionId);
        if (! $subscription) {
            return;
        }

        $subscription->recalculatePaymentSummary();
    }
}
