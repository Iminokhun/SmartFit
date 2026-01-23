<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    protected $fillable = [
        'user_id',
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
}
