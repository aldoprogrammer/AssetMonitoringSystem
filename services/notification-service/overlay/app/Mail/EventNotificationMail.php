<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EventNotificationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly string $eventType,
        private readonly array $payload,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: "AssetMonitoringSystem Event: {$this->eventType}");
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.event-notification',
            with: [
                'eventType' => $this->eventType,
                'payload' => $this->payload,
            ],
        );
    }
}
