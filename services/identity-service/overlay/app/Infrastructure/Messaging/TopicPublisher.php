<?php

namespace App\Infrastructure\Messaging;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class TopicPublisher
{
    public function publish(string $routingKey, array $payload): void
    {
        $config = config('services.rabbitmq');
        $connection = new AMQPStreamConnection(
            $config['host'],
            (int) $config['port'],
            $config['user'],
            $config['password'],
            $config['vhost'],
        );

        /** @var AMQPChannel $channel */
        $channel = $connection->channel();
        $channel->exchange_declare($config['exchange'], 'topic', false, true, false);

        $message = new AMQPMessage(
            json_encode($payload, JSON_THROW_ON_ERROR),
            [
                'content_type' => 'application/json',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                'message_id' => $payload['message_id'] ?? (string) str()->uuid(),
                'type' => $payload['event_type'] ?? $routingKey,
            ],
        );

        $channel->basic_publish($message, $config['exchange'], $routingKey);
        $channel->close();
        $connection->close();
    }
}
