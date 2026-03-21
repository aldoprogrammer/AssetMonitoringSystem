<?php

namespace App\Infrastructure\Messaging;

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

        $channel = $connection->channel();
        $channel->exchange_declare($config['exchange'], 'topic', false, true, false);

        $channel->basic_publish(
            new AMQPMessage(json_encode($payload, JSON_THROW_ON_ERROR), [
                'content_type' => 'application/json',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                'message_id' => $payload['message_id'] ?? (string) str()->uuid(),
            ]),
            $config['exchange'],
            $routingKey,
        );

        $channel->close();
        $connection->close();
    }
}
