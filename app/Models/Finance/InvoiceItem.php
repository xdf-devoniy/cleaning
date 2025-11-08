<?php

namespace App\Models\Finance;

use App\Models\ServiceCatalog\Service;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
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

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
