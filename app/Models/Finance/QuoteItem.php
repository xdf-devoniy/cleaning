<?php

namespace App\Models\Finance;

use App\Models\ServiceCatalog\Service;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuoteItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'quote_id',
        'service_id',
        'description',
        'quantity',
        'unit_price',
        'discount',
        'tax_rate',
        'total',
    ];

    protected $casts = [
        'quantity' => 'float',
        'unit_price' => 'float',
        'discount' => 'float',
        'tax_rate' => 'float',
        'total' => 'float',
    ];

    public function quote()
    {
        return $this->belongsTo(Quote::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
