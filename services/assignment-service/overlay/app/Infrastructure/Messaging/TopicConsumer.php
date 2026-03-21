<?php

namespace App\Infrastructure\Messaging;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Throwable;

class TopicConsumer
{
    public function consume(string $consumerName, array $bindingKeys, callable $handler): void
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

        [$queueName] = $channel->queue_declare(
            "{$config['queue_prefix']}.{$consumerName}",
            false,
            true,
            false,
            false,
        );

        foreach ($bindingKeys as $bindingKey) {
            $channel->queue_bind($queueName, $config['exchange'], $bindingKey);
        }

        $channel->basic_qos(0, 1, false);
        $channel->basic_consume($queueName, '', false, false, false, false, function (AMQPMessage $message) use ($handler): void {
            try {
                $handler(json_decode($message->getBody(), true, 512, JSON_THROW_ON_ERROR), $message);
                $message->ack();
            } catch (Throwable) {
                $message->nack(false, false);
            }
        });

        while ($channel->is_consuming()) {
            $channel->wait();
        }
    }
}
