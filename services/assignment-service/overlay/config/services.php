<?php

return [
    'inventory' => [
        'base_url' => env('INVENTORY_SERVICE_BASE_URL', 'http://inventory-service:8000'),
        'timeout' => (int) env('CIRCUIT_BREAKER_TIMEOUT_SECONDS', 2),
    ],
    'circuit_breaker' => [
        'failure_threshold' => (int) env('CIRCUIT_BREAKER_FAILURE_THRESHOLD', 3),
        'open_seconds' => (int) env('CIRCUIT_BREAKER_OPEN_SECONDS', 30),
    ],
    'rabbitmq' => [
        'host' => env('RABBITMQ_HOST', 'rabbitmq'),
        'port' => env('RABBITMQ_PORT', 5672),
        'user' => env('RABBITMQ_USER', 'guest'),
        'password' => env('RABBITMQ_PASSWORD', 'guest'),
        'vhost' => env('RABBITMQ_VHOST', '/'),
        'exchange' => env('RABBITMQ_EXCHANGE', 'asset_monitoring_system.events'),
        'queue_prefix' => env('RABBITMQ_QUEUE_PREFIX', 'assignment'),
    ],
];
