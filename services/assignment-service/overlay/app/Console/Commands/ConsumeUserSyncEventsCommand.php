<?php

namespace App\Console\Commands;

use App\Infrastructure\Messaging\IdempotencyService;
use App\Infrastructure\Messaging\TopicConsumer;
use App\Services\UserSyncService;
use Illuminate\Console\Command;
use PhpAmqpLib\Message\AMQPMessage;

class ConsumeUserSyncEventsCommand extends Command
{
    protected $signature = 'assignments:consume-user-sync';

    protected $description = 'Consume user.* events and project them into the assignment service.';

    public function handle(
        TopicConsumer $consumer,
        IdempotencyService $idempotency,
        UserSyncService $userSync,
    ): int {
        $consumer->consume('user-sync', ['user.*'], function (array $event, AMQPMessage $message) use ($idempotency, $userSync): void {
            $messageId = $message->get('message_id') ?: ($event['message_id'] ?? '');

            if ($messageId !== '' && $idempotency->wasProcessed('user-sync', $messageId)) {
                return;
            }

            $userSync->sync($event['payload']);

            if ($messageId !== '') {
                $idempotency->markProcessed('user-sync', $messageId);
            }
        });

        return self::SUCCESS;
    }
}
