<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryMovement extends Model
{
    protected $fillable = [
        'inventory_id',
        'type',
        'quantity',
        'description',
    ];

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }
    public function isIn(): bool
    {
        return $this->type === 'in';
    }

    public function isOut(): bool
    {
        return $this->type === 'out';
    }
}
