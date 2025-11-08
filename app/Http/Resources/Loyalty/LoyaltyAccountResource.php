<?php

namespace App\Http\Resources\Loyalty;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoyaltyAccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'points_balance' => $this->points_balance,
            'lifetime_points' => $this->lifetime_points,
            'tier' => $this->tier,
            'last_redeemed_at' => $this->last_redeemed_at?->toIso8601String(),
            'transactions' => LoyaltyTransactionResource::collection($this->whenLoaded('transactions')),
            'referrals' => ReferralResource::collection($this->whenLoaded('referrals')),
        ];
    }
}
