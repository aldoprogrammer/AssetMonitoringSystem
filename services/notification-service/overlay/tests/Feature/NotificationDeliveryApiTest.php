<?php

namespace Tests\Feature;

use App\Models\NotificationDelivery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationDeliveryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_deliveries_endpoint_returns_paginated_records(): void
    {
        NotificationDelivery::create([
            'event_type' => 'assignment.checked_out',
            'recipient' => 'user@example.com',
            'channel' => 'email',
            'status' => 'sent',
            'payload' => ['asset_serial_number' => 'LAP-1001'],
            'delivered_at' => now(),
        ]);

        $this->getJson(route('notification-deliveries.index', absolute: false))
            ->assertOk()
            ->assertJsonPath('data.0.channel', 'email');
    }
}
