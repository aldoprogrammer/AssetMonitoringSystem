<?php

namespace App\Console\Commands;

use App\Infrastructure\Messaging\IdempotencyService;
use App\Infrastructure\Messaging\TopicConsumer;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use PhpAmqpLib\Message\AMQPMessage;

class ConsumeNotificationEventsCommand extends Command
{
    protected $signature = 'notifications:consume-events';

    protected $description = 'Consume assignment and health alerts and dispatch notifications.';

    public function handle(
        TopicConsumer $consumer,
        IdempotencyService $idempotency,
        NotificationService $notifications,
    ): int {
        $consumer->consume('event-alerts', ['assignment.*', 'health.*'], function (array $event, AMQPMessage $message) use ($idempotency, $notifications): void {
            $messageId = $message->get('message_id') ?: ($event['message_id'] ?? '');

            if ($messageId !== '' && $idempotency->wasProcessed('event-alerts', $messageId)) {
                return;
            }

            $notifications->dispatch($event);

            if ($messageId !== '') {
                $idempotency->markProcessed('event-alerts', $messageId);
            }
        });

        return self::SUCCESS;
    }
}
