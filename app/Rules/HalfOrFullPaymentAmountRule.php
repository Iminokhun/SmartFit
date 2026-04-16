<?php

namespace App\Rules;
use App\Models\Subscription;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class HalfOrFullPaymentAmountRule implements ValidationRule
{
    public function __construct(private readonly int $subscriptionId) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->subscriptionId <= 0) {
            $fail('Select subscription first.');
            return;
        }

        $subscription = Subscription::query()->find($this->subscriptionId);
        if (!$subscription) {
            $fail('Subscription not found.');
            return;
        }

        $full = round((float) $subscription->finalPrice(), 2);
        $half = round((float) $full/2, 2);
        $amount = round((float) $value, 2);

        if ($amount !== $full && $amount !== $half) {
            $fail("Amount must be either 50% ({$half}) or full ({$full}).");
        }
    }
}
