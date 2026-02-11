<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Staff extends Model
{
    protected $fillable = [
        'user_id',
        'role_id',
        'full_name',
        'specialization',
        'experience_years',
        'phone',
        'email',
        'photo',
        'status',
        'salary_type',
        'salary',
    ];

    protected $casts = [
        'status' => 'string',
        'salary_type' => 'string',
        'salary' => 'decimal:2',
    ];



    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'trainer_id');
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function shifts()
    {
        return $this->hasMany(Shift::class);
    }

    public function activities()
    {
        return $this->hasManyThrough(
            Activity::class,
            Schedule::class,
            'trainer_id',
            'id',
            'id',
            'activity_id'
        );
    }
}
