<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable = [
        'name',
        'description',
        'duration_days',
        'price',
        'visits_limit',
        'activity_id',
        'discount',
    ];


    public function customers()
    {
        return $this->hasMany(CustomerSubscription::class);
    }

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }
}
