<?php

namespace App\Models\ServiceCatalog;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'base_price',
        'duration_minutes',
        'pricing_type',
        'description',
        'category',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'base_price' => 'float',
        'duration_minutes' => 'integer',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function priceTiers()
    {
        return $this->hasMany(ServicePrice::class);
    }

    public function bundles()
    {
        return $this->belongsToMany(ServiceBundle::class, 'service_bundle_items');
    }
}
