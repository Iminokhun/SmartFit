<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerSubscription extends Model
{
    protected $fillable = [
        'customer_id',
        'subscription_id',
        'start_date',
        'end_date',
        'remaining_visits',
        'status',
        'paid_amount',
        'debt',
        'payment_status',
    ];

    protected $casts = [
        'paid_amount' => 'decimal:2',
        'debt' => 'decimal:2',
    ];

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    public function finalPrice(): float
    {
        return $this->subscription?->finalPrice() ?? 0.0;
    }

    public function recalculatePaymentSummary(): void
    {
        $this->loadMissing('subscription');

        $finalPrice = $this->finalPrice();
        $paidAmount = (float) $this->payments()
            ->whereIn('status', ['paid', 'partial'])
            ->sum('amount');

        $paidAmount = round($paidAmount, 2);
        $debt = max(0, round($finalPrice - $paidAmount, 2));

        if ($finalPrice <= 0) {
            $paymentStatus = 'paid';
        } elseif ($paidAmount >= $finalPrice) {
            $paymentStatus = 'paid';
        } elseif ($paidAmount > 0) {
            $paymentStatus = 'partial';
        } else {
            $paymentStatus = 'unpaid';
        }

        $this->forceFill([
            'paid_amount' => $paidAmount,
            'debt' => $debt,
            'payment_status' => $paymentStatus,
        ])->saveQuietly();
    }
}
