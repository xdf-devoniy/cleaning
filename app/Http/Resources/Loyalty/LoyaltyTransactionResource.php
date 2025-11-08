<?php

namespace App\Http\Resources\Loyalty;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoyaltyTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'points' => $this->points,
            'reference' => [
                'type' => $this->reference_type,
                'id' => $this->reference_id,
            ],
            'expires_at' => $this->expires_at?->toIso8601String(),
            'metadata' => $this->metadata,
        ];
    }
}
