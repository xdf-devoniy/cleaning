<?php

namespace App\Models\ServiceCatalog;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServicePrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_id',
        'location',
        'client_type',
        'price',
        'currency',
        'unit',
        'valid_from',
        'valid_to',
    ];

    protected $casts = [
        'price' => 'float',
        'valid_from' => 'date',
        'valid_to' => 'date',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
