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

    public function finalPrice(): float
    {
        $price = (float) ($this->price ?? 0);
        $discount = (float) ($this->discount ?? 0);
        $final = $price - ($price * $discount / 100);

        return max(0, round($final, 2));
    }
}
