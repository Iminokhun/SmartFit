<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    protected $fillable = [
        'staff_id',
        'days_of_week',
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'days_of_week' => 'array',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}
