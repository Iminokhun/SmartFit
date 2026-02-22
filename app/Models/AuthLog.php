<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuthLog extends Model
{
    protected $fillable = [
        'user_id',
        'email',
        'panel',
        'guard',
        'status',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

