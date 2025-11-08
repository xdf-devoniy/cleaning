<?php

namespace App\Models\Finance;

use App\Enums\PaymentStatus;
use App\Models\CRM\Client;
use App\Models\Finance\InvoiceItem;
use App\Models\Finance\InvoicePayment;
use App\Models\Finance\Quote;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'client_id',
        'quote_id',
        'number',
        'status',
        'subtotal',
        'tax_total',
        'discount_total',
        'total',
        'balance_due',
        'currency',
        'issued_at',
        'due_at',
        'notes',
    ];

    protected $casts = [
        'subtotal' => 'float',
        'tax_total' => 'float',
        'discount_total' => 'float',
        'total' => 'float',
        'balance_due' => 'float',
        'issued_at' => 'datetime',
        'due_at' => 'datetime',
        'status' => PaymentStatus::class,
    ];

    public function scopePaid($query)
    {
        return $query->where('status', PaymentStatus::Paid);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments()
    {
        return $this->hasMany(InvoicePayment::class);
    }

    public function quote()
    {
        return $this->belongsTo(Quote::class);
    }
}
