<?php

namespace App\Http\Resources\CRM;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientNoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'body' => $this->body,
            'pinned' => $this->pinned,
            'created_at' => $this->created_at?->toIso8601String(),
            'author' => [
                'id' => $this->author?->id,
                'name' => $this->author?->name,
            ],
        ];
    }
}
