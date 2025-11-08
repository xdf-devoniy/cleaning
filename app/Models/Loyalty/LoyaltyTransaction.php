<?php

namespace App\Models\Loyalty;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoyaltyTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'type',
        'points',
        'reference_type',
        'reference_id',
        'expires_at',
        'metadata',
    ];

    protected $casts = [
        'points' => 'float',
        'expires_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function account()
    {
        return $this->belongsTo(LoyaltyAccount::class, 'account_id');
    }
}
