<?php

namespace App\Models\Inventory;

use App\Models\Scheduling\Job;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryUsage extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_item_id',
        'job_id',
        'quantity',
        'used_at',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'float',
        'used_at' => 'datetime',
    ];

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function job()
    {
        return $this->belongsTo(Job::class);
    }
}
