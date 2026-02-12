<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    protected $fillable = [
        'activity_id',
        'trainer_id',
        'hall_id',
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

    public function hall()
    {
        return $this->belongsTo(Hall::class);
    }

    protected function timeRange(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn () => sprintf(
                '%s - %s',
                \Carbon\Carbon::parse($this->start_time)->format('H:i'),
                \Carbon\Carbon::parse($this->end_time)->format('H:i'),
            ),
        );
    }
}
