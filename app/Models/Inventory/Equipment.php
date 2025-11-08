<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Equipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'serial_number',
        'purchase_date',
        'warranty_expiry',
        'assigned_to',
        'status',
        'maintenance_schedule',
        'notes',
    ];

    protected $casts = [
        'purchase_date' => 'datetime',
        'warranty_expiry' => 'datetime',
        'maintenance_schedule' => 'array',
    ];
}
