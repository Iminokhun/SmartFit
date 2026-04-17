<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramLink extends Model
{
    protected $fillable = [
        'customer_id',
        'telegram_user_id',
        'telegram_username',
        'first_name',
        'last_name',
        'is_verified',
        'linked_at',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'linked_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}

