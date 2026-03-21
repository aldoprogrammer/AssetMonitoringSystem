<?php

namespace App\Console\Commands;

use App\Infrastructure\Messaging\IdempotencyService;
use App\Infrastructure\Messaging\TopicConsumer;
use App\Services\AuditLogService;
use Illuminate\Console\Command;
use PhpAmqpLib\Message\AMQPMessage;

class ConsumeAuditEventsCommand extends Command
{
    protected $signature = 'audit:consume-events';

    protected $description = 'Consume all AssetMonitoringSystem domain events and persist centralized audit records.';

    public function handle(
        TopicConsumer $consumer,
        IdempotencyService $idempotency,
        AuditLogService $auditLogs,
    ): int {
        $consumer->consume('all-events', ['user.*', 'assignment.*', 'audit.*', 'health.*'], function (array $event, AMQPMessage $message) use ($idempotency, $auditLogs): void {
            $messageId = $message->get('message_id') ?: ($event['message_id'] ?? '');
            $routingKey = $message->delivery_info['routing_key'] ?? ($event['event_type'] ?? 'unknown');

            if ($messageId !== '' && $idempotency->wasProcessed('all-events', $messageId)) {
                return;
            }

            $auditLogs->record($event, $routingKey);

            if ($messageId !== '') {
                $idempotency->markProcessed('all-events', $messageId);
            }
        });

        return self::SUCCESS;
    }
}
