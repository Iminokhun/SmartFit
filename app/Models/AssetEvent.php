<?php

namespace App\Models;

use App\Enums\AssetEventType;
use App\Enums\InventoryStatus;
use Illuminate\Database\Eloquent\Model;

class AssetEvent extends Model
{
    protected $fillable = [
        'inventory_id',
        'event_type',
        'event_date',
        'from_hall_id',
        'to_hall_id',
        'condition_before',
        'condition_after',
        'status_before',
        'status_after',
        'note',
    ];

    protected $casts = [
        'event_type' => AssetEventType::class,
        'event_date' => 'datetime',
        'status_before' => InventoryStatus::class,
        'status_after' => InventoryStatus::class,
    ];

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    public function fromHall()
    {
        return $this->belongsTo(Hall::class, 'from_hall_id');
    }

    public function toHall()
    {
        return $this->belongsTo(Hall::class, 'to_hall_id');
    }
}
