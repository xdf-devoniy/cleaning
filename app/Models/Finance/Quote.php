<?php

namespace App\Models\Finance;

use App\Models\CRM\Client;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quote extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'client_id',
        'number',
        'status',
        'subtotal',
        'tax_total',
        'discount_total',
        'total',
        'currency',
        'valid_until',
        'notes',
    ];

    protected $casts = [
        'subtotal' => 'float',
        'tax_total' => 'float',
        'discount_total' => 'float',
        'total' => 'float',
        'valid_until' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function items()
    {
        return $this->hasMany(QuoteItem::class);
    }
}
