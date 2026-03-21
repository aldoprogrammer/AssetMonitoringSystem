<?php

namespace Tests\Feature;

use App\Infrastructure\Messaging\IdempotencyService;
use App\Services\UserSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpAmqpLib\Message\AMQPMessage;
use Tests\TestCase;

class RabbitMqPublishConsumeFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_sync_consumer_is_idempotent_for_duplicate_messages(): void
    {
        $event = [
            'message_id' => 'msg-user-created-1',
            'event_type' => 'user.created',
            'occurred_at' => now()->toIso8601String(),
            'source_service' => 'identity-service',
            'payload' => [
                'id' => 202,
                'employee_id' => 8001,
                'name' => 'Sam Staff',
                'email' => 'sam.staff@asset_monitoring_system.local',
                'role' => 'staff',
            ],
        ];

        $message = new AMQPMessage(
            json_encode($event, JSON_THROW_ON_ERROR),
            ['message_id' => 'msg-user-created-1'],
        );

        $idempotency = app(IdempotencyService::class);
        $userSync = app(UserSyncService::class);

        $handler = function (array $event, AMQPMessage $message) use ($idempotency, $userSync): void {
            $messageId = $message->get('message_id') ?: ($event['message_id'] ?? '');

            if ($messageId !== '' && $idempotency->wasProcessed('user-sync', $messageId)) {
                return;
            }

            $userSync->sync($event['payload']);

            if ($messageId !== '') {
                $idempotency->markProcessed('user-sync', $messageId);
            }
        };

        $handler($event, $message);
        $handler($event, $message);

        $this->assertDatabaseCount('processed_messages', 1);
        $this->assertDatabaseCount('user_projections', 1);
        $this->assertDatabaseHas('user_projections', [
            'external_user_id' => 202,
            'email' => 'sam.staff@asset_monitoring_system.local',
        ]);
    }
}
