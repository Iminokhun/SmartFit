<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CheckinToken extends Model
{
    protected $fillable = [
        'customer_id',
        'token_hash',
        'expires_at',
        'used_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function checkin()
    {
        return $this->hasOne(CustomerCheckin::class);
    }
}

