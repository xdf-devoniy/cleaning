<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'inventory_item_id',
        'quantity',
        'unit_cost',
        'total',
    ];

    protected $casts = [
        'quantity' => 'float',
        'unit_cost' => 'float',
        'total' => 'float',
    ];

    public function order()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}
