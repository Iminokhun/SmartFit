<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiChatMessage extends Model
{
    protected $fillable = [
        'telegram_user_id',
        'role',
        'content',
        'is_photo',
    ];

    protected $casts = [
        'is_photo' => 'boolean',
    ];
}
