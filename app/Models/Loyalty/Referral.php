<?php

namespace App\Models\Loyalty;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Referral extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'code',
        'referred_client_id',
        'redeemed_at',
        'bonus_points',
    ];

    protected $casts = [
        'redeemed_at' => 'datetime',
        'bonus_points' => 'float',
    ];

    public function account()
    {
        return $this->belongsTo(LoyaltyAccount::class, 'account_id');
    }
}
