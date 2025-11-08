<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'contact_name',
        'email',
        'phone',
        'address',
        'notes',
        'rating',
    ];

    protected $casts = [
        'rating' => 'float',
    ];

    public function items()
    {
        return $this->hasMany(InventoryItem::class);
    }
}
