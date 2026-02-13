<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'amount',
        'expenses_date',
        'description',
        'staff_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expenses_date' => 'date',
    ];

    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class);
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}
