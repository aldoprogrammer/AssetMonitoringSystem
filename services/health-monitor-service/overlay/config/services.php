<?php

return [
    'health' => [
        'device_inactive_after_minutes' => (int) env('DEVICE_INACTIVE_AFTER_MINUTES', 10),
    ],
    'rabbitmq' => [
        'host' => env('RABBITMQ_HOST', 'rabbitmq'),
        'port' => env('RABBITMQ_PORT', 5672),
        'user' => env('RABBITMQ_USER', 'guest'),
        'password' => env('RABBITMQ_PASSWORD', 'guest'),
        'vhost' => env('RABBITMQ_VHOST', '/'),
        'exchange' => env('RABBITMQ_EXCHANGE', 'asset_monitoring_system.events'),
        'queue_prefix' => env('RABBITMQ_QUEUE_PREFIX', 'health'),
    ],
];
