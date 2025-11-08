<?php

namespace App\Http\Resources\CRM;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'lead_status' => $this->lead_status,
            'tags' => $this->tags,
            'source' => $this->source,
            'preferred_language' => $this->preferred_language,
            'notes' => $this->notes,
            'lifetime_value' => $this->lifetime_value,
            'ar_balance' => $this->accounts_receivable_balance,
            'last_activity_at' => optional($this->last_activity_at)?->toIso8601String(),
            'loyalty' => new \App\Http\Resources\Loyalty\LoyaltyAccountResource($this->whenLoaded('loyaltyAccount')),
            'contacts' => ClientContactResource::collection($this->whenLoaded('contacts')),
            'addresses' => ClientAddressResource::collection($this->whenLoaded('addresses')),
            'notes_collection' => ClientNoteResource::collection($this->whenLoaded('notes')),
            'reminders' => ClientReminderResource::collection($this->whenLoaded('reminders')),
            'communication_logs' => CommunicationLogResource::collection($this->whenLoaded('communicationLogs')),
        ];
    }
}
