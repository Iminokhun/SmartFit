<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramStaffLink extends Model
{
    protected $fillable = [
        'user_id',
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

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

