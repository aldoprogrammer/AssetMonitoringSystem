<?php

namespace App\Services;

use App\Mail\EventNotificationMail;
use App\Repositories\Contracts\NotificationDeliveryRepositoryInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    public function __construct(private readonly NotificationDeliveryRepositoryInterface $deliveries)
    {
    }

    public function paginate(int $perPage = 25)
    {
        return $this->deliveries->paginate($perPage);
    }

    public function dispatch(array $event): void
    {
        $recipient = data_get($event, 'payload.user_email', 'ops@assetmonitoringsystem.local');
        $eventType = $event['event_type'] ?? 'unknown';
        $payload = $event['payload'] ?? [];

        Mail::to($recipient)->send(new EventNotificationMail($eventType, $payload));
        $this->deliveries->create([
            'event_type' => $eventType,
            'recipient' => $recipient,
            'channel' => 'email',
            'status' => 'sent',
            'payload' => $payload,
            'delivered_at' => now(),
        ]);

        if ($webhookUrl = config('services.slack.webhook_url')) {
            Http::post($webhookUrl, [
                'text' => sprintf('[%s] %s', $eventType, json_encode($payload, JSON_THROW_ON_ERROR)),
            ]);

            $this->deliveries->create([
                'event_type' => $eventType,
                'recipient' => $webhookUrl,
                'channel' => 'slack',
                'status' => 'sent',
                'payload' => $payload,
                'delivered_at' => now(),
            ]);
        }
    }
}
