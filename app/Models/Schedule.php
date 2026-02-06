<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    protected $fillable = [
        'activity_id',
        'trainer_id',
        'hall',
        'days_of_week',
        'start_time',
        'end_time',
        'max_participants',
    ];

    protected $casts = [
        'days_of_week' => 'array',
    ];

    public function occurrences()
    {
        return $this->hasMany(ScheduleOccurrence::class);
    }

    public function visits()
    {
        return $this->hasMany(Visit::class);
    }

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class, 'trainer_id');
    }
}
