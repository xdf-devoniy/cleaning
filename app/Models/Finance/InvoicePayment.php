<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoicePayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'amount',
        'paid_at',
        'method',
        'reference',
        'meta',
    ];

    protected $casts = [
        'amount' => 'float',
        'paid_at' => 'datetime',
        'meta' => 'array',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
