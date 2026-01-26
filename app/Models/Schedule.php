<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    protected $fillable = [
        'activity_id',
        'trainer_id',
        'hall',
        'day_of_week',
        'start_time',
        'end_time',
        'max_participants',
    ];

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class, 'trainer_id');
    }
}
