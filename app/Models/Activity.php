<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    protected $fillable = [
        'name',
        'activity_category_id',
        'icon'
    ];

    public function category()
    {
        return $this->belongsTo(ActivityCategory::class, 'activity_category_id');
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }
}
