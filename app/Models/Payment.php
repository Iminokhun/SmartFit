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
