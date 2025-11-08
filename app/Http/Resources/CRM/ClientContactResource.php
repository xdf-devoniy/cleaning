<?php

namespace App\Http\Resources\CRM;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientContactResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'is_primary' => $this->is_primary,
            'notes' => $this->notes,
        ];
    }
}
