<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_type' => $this->event_type,
            'routing_key' => $this->routing_key,
            'source_service' => $this->source_service,
            'payload' => $this->payload,
            'occurred_at' => $this->occurred_at?->toIso8601String(),
        ];
    }
}
