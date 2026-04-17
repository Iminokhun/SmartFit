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
        'allowed_weekdays',
        'time_from',
        'time_to',
        'max_checkins_per_day',
        'freeze_days_limit',
        'hall_id',
        'trainer_id'
    ];

    protected $casts = [
        'allowed_weekdays' => 'array',
        'time_from' => 'datetime:H:i:s',
        'time_to' => 'datetime:H:i:s',
        'max_checkins_per_day' => 'integer',
        'freeze_days_limit' => 'integer',
    ];

    public function customers()
    {
        return $this->hasMany(CustomerSubscription::class);
    }

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }

    public function trainer()
    {
        return $this->belongsTo(Staff::class, 'trainer_id');
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    public function hall()
    {
        return $this->belongsTo(\App\Models\Hall::class);
    }

    public function finalPrice(): float
    {
        $price = (float) ($this->price ?? 0);
        $discount = (float) ($this->discount ?? 0);
        $final = $price - ($price * $discount / 100);

        return max(0, round($final, 2));
    }

    public function capacityUsed(): int
    {
        return (int) ($this->customers_count ?? $this->customers()->count());
    }

    public function capacityAvailable(): ?int
    {
        $limit = $this->capacityLimit();

        if ($limit === null) {
            return null;
        }

        return max(0, $limit - $this->capacityUsed());
    }

    public function capacityLimit(): ?int
    {
        $limit = $this->visits_limit;

        if ($limit === null) {
            return null;
        }

        $limit = (int) $limit;

        return $limit > 0 ? $limit : null;
    }

    public function capacityLabel(): string
    {
        $limit = $this->capacityLimit();

        if ($limit === null) {
            return 'Unlimited';
        }

        return "Available {$this->capacityAvailable()}";
    }

    public function capacityColor(): string
    {
        $limit = $this->capacityLimit();

        if ($limit === null) {
            return 'gray';
        }

        return $this->capacityUsed() >= $limit ? 'danger' : 'success';
    }
}

