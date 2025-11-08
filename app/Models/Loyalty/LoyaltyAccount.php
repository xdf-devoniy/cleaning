<?php

namespace App\Models\Loyalty;

use App\Enums\LoyaltyTier;
use App\Models\CRM\Client;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoyaltyAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'points_balance',
        'lifetime_points',
        'tier',
        'last_redeemed_at',
    ];

    protected $casts = [
        'points_balance' => 'float',
        'lifetime_points' => 'float',
        'tier' => LoyaltyTier::class,
        'last_redeemed_at' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function transactions()
    {
        return $this->hasMany(LoyaltyTransaction::class, 'account_id');
    }

    public function referrals()
    {
        return $this->hasMany(Referral::class, 'account_id');
    }
}
