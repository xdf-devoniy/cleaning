<?php

namespace App\Models\HR;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffLoan extends Model
{
    use HasFactory;

    protected $fillable = [
        'staff_id',
        'amount',
        'issued_at',
        'balance',
        'installment',
        'notes',
    ];

    protected $casts = [
        'amount' => 'float',
        'balance' => 'float',
        'installment' => 'float',
        'issued_at' => 'date',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}
