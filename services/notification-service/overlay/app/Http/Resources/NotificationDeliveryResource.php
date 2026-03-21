<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationDeliveryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_type' => $this->event_type,
            'recipient' => $this->recipient,
            'channel' => $this->channel,
            'status' => $this->status,
            'payload' => $this->payload,
            'delivered_at' => $this->delivered_at?->toIso8601String(),
        ];
    }
}
