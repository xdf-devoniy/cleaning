<?php

namespace App\Http\Resources\CRM;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommunicationLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'channel' => $this->channel,
            'subject' => $this->subject,
            'direction' => $this->direction,
            'occurred_at' => $this->occurred_at?->toIso8601String(),
            'payload' => $this->payload,
        ];
    }
}
