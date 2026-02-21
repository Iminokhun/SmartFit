<?php

namespace App\Models;

use App\Enums\InventoryItemType;
use App\Enums\InventoryStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category_id',
        'item_type',
        'quantity',
        'unit',
        'min_quantity',
        'cost_price',
        'sell_price',
        'asset_tag',
        'serial_number',
        'condition',
        'hall_id',
        'purchase_date',
        'purchase_price',
        'warranty_until',
        'status',
        'expense_id',
    ];

    protected $casts = [
        'item_type' => InventoryItemType::class,
        'status' => InventoryStatus::class,
        'purchase_date' => 'date',
        'warranty_until' => 'date',
        'cost_price' => 'decimal:2',
        'sell_price' => 'decimal:2',
        'purchase_price' => 'decimal:2',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function hall()
    {
        return $this->belongsTo(Hall::class);
    }

    public function movements()
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function expense()
    {
        return $this->belongsTo(Expense::class);
    }

    public function isAsset(): bool
    {
        return $this->item_type === InventoryItemType::Asset;
    }

    public function isConsumableOrRetail(): bool
    {
        return in_array($this->item_type, [InventoryItemType::Consumable, InventoryItemType::Retail], true);
    }
}


