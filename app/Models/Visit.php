<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Visit extends Model
{
    protected $fillable = [
        'customer_id',
        'schedule_id',
        'occurrence_id',
        'trainer_id',
        'visited_at',
        'status',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    public function occurrence()
    {
        return $this->belongsTo(ScheduleOccurrence::class, 'occurrence_id');
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class, 'trainer_id');
    }
}
