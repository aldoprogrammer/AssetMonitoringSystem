<?php

namespace Tests\Unit;

use App\Mail\EventNotificationMail;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatch_sends_email_and_slack_and_persists_delivery_records(): void
    {
        Mail::fake();
        Http::fake();

        config(['services.slack.webhook_url' => 'https://hooks.slack.test/services/asset-monitoring']);

        app(NotificationService::class)->dispatch([
            'event_type' => 'assignment.checked_out',
            'payload' => [
                'user_email' => 'user@example.com',
                'asset_serial_number' => 'LAP-1001',
            ],
        ]);

        Mail::assertSent(EventNotificationMail::class);
        Http::assertSentCount(1);

        $this->assertDatabaseHas('notification_deliveries', [
            'event_type' => 'assignment.checked_out',
            'recipient' => 'user@example.com',
            'channel' => 'email',
            'status' => 'sent',
        ]);

        $this->assertDatabaseHas('notification_deliveries', [
            'event_type' => 'assignment.checked_out',
            'recipient' => 'https://hooks.slack.test/services/asset-monitoring',
            'channel' => 'slack',
            'status' => 'sent',
        ]);
    }
}
