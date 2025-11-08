<?php

namespace App\Http\Resources\Loyalty;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReferralResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'referred_client_id' => $this->referred_client_id,
            'redeemed_at' => $this->redeemed_at?->toIso8601String(),
            'bonus_points' => $this->bonus_points,
        ];
    }
}
