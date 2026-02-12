<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduleOccurrence extends Model
{
    protected $fillable = [
        'schedule_id',
        'date',
        'start_time',
        'end_time',
        'max_participants',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    public function visits()
    {
        return $this->hasMany(Visit::class, 'occurrence_id');
    }
}

