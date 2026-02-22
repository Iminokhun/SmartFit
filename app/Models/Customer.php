<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    protected $fillable = [
        'full_name',
        'birth_date',
        'phone',
        'email',
        'gender',
        'photo',
        'status',
    ];

    public function subscriptions()
    {
        return $this->hasMany(CustomerSubscription::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function visits()
    {
        return $this->hasMany(Visit::class);
    }
}
