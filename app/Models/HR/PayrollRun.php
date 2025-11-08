<?php

namespace App\Models\HR;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'period_start',
        'period_end',
        'status',
        'processed_by',
        'gross_total',
        'net_total',
        'notes',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'gross_total' => 'float',
        'net_total' => 'float',
    ];

    public function items()
    {
        return $this->hasMany(PayrollItem::class);
    }
}
