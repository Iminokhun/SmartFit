<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerCheckin extends Model
{
    protected $fillable = [
        'customer_id',
        'customer_subscription_id',
        'schedule_id',
        'checkin_token_id',
        'checked_in_by_user_id',
        'checked_in_at',
    ];

    protected $casts = [
        'checked_in_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function schedule()
    {
        return  $this->belongsTo(Schedule::class);
    }
    public function customerSubscription()
    {
        return $this->belongsTo(CustomerSubscription::class);
    }

    public function token()
    {
        return $this->belongsTo(CheckinToken::class, 'checkin_token_id');
    }

    public function checkedBy()
    {
        return $this->belongsTo(User::class, 'checked_in_by_user_id');
    }
}


