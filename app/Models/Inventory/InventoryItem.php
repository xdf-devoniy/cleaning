<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'sku',
        'category',
        'unit',
        'on_hand',
        'cost_price',
        'min_stock_level',
        'metadata',
        'supplier_id',
    ];

    protected $casts = [
        'on_hand' => 'float',
        'cost_price' => 'float',
        'min_stock_level' => 'float',
        'metadata' => 'array',
    ];

    public function usages()
    {
        return $this->hasMany(InventoryUsage::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}
