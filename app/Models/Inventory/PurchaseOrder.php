<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'number',
        'status',
        'order_date',
        'expected_at',
        'total',
        'notes',
    ];

    protected $casts = [
        'order_date' => 'datetime',
        'expected_at' => 'datetime',
        'total' => 'float',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }
}
