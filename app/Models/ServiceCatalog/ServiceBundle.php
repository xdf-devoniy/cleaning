<?php

namespace App\Models\ServiceCatalog;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceBundle extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'discount_rate',
        'is_active',
    ];

    protected $casts = [
        'discount_rate' => 'float',
        'is_active' => 'boolean',
    ];

    public function services()
    {
        return $this->belongsToMany(Service::class, 'service_bundle_items');
    }
}
