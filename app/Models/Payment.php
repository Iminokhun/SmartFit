<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'customer_id',
        'customer_subscription_id',
        'amount',
        'method',
        'status',
        'description',
        'telegram_payment_charge_id',
        'provider_payment_charge_id',
    ];

    protected $casts = [
        'method' => PaymentMethod::class,
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function customerSubscription()
    {
        return $this->belongsTo(CustomerSubscription::class);
    }
}
