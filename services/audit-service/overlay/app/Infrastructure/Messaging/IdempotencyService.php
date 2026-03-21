<?php

namespace App\Infrastructure\Messaging;

use App\Models\ProcessedMessage;

class IdempotencyService
{
    public function wasProcessed(string $consumer, string $messageId): bool
    {
        return ProcessedMessage::query()
            ->where('consumer', $consumer)
            ->where('message_id', $messageId)
            ->exists();
    }

    public function markProcessed(string $consumer, string $messageId): void
    {
        ProcessedMessage::query()->firstOrCreate([
            'consumer' => $consumer,
            'message_id' => $messageId,
        ], [
            'processed_at' => now(),
        ]);
    }
}
