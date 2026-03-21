<?php

namespace App\Support\CircuitBreaker;

use App\Enums\CircuitBreakerState;
use Carbon\Carbon;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Throwable;

class CircuitBreaker
{
    public function __construct(
        private readonly CacheRepository $cache,
        private readonly string $name,
        private readonly int $failureThreshold,
        private readonly int $openSeconds,
    ) {
    }

    public function call(callable $action, callable $fallback): mixed
    {
        $snapshot = $this->snapshot();

        if ($snapshot['state'] === CircuitBreakerState::OPEN && ! $this->openWindowExpired($snapshot)) {
            return $fallback($snapshot['state'], null);
        }

        if ($snapshot['state'] === CircuitBreakerState::OPEN && $this->openWindowExpired($snapshot)) {
            $snapshot = $this->transitionTo(CircuitBreakerState::HALF_OPEN, 0);
        }

        try {
            $result = $action();
            $this->transitionTo(CircuitBreakerState::CLOSED, 0);

            return $result;
        } catch (Throwable $exception) {
            $state = $this->registerFailure($snapshot);

            return $fallback($state, $exception);
        }
    }

    public function currentState(): CircuitBreakerState
    {
        return $this->snapshot()['state'];
    }

    public function snapshot(): array
    {
        $payload = $this->cache->get($this->cacheKey(), [
            'state' => CircuitBreakerState::CLOSED->value,
            'failure_count' => 0,
            'opened_at' => null,
        ]);

        return [
            'state' => CircuitBreakerState::from($payload['state']),
            'failure_count' => (int) $payload['failure_count'],
            'opened_at' => $payload['opened_at'],
        ];
    }

    private function registerFailure(array $snapshot): CircuitBreakerState
    {
        if ($snapshot['state'] === CircuitBreakerState::HALF_OPEN) {
            $this->transitionTo(CircuitBreakerState::OPEN, $this->failureThreshold);

            return CircuitBreakerState::OPEN;
        }

        $failures = $snapshot['failure_count'] + 1;

        if ($failures >= $this->failureThreshold) {
            $this->transitionTo(CircuitBreakerState::OPEN, $failures);

            return CircuitBreakerState::OPEN;
        }

        $this->persist(CircuitBreakerState::CLOSED, $failures, null);

        return CircuitBreakerState::CLOSED;
    }

    private function transitionTo(CircuitBreakerState $state, int $failureCount): array
    {
        $openedAt = $state === CircuitBreakerState::OPEN ? now()->toIso8601String() : null;
        $this->persist($state, $failureCount, $openedAt);

        return $this->snapshot();
    }

    private function persist(CircuitBreakerState $state, int $failureCount, ?string $openedAt): void
    {
        $this->cache->forever($this->cacheKey(), [
            'state' => $state->value,
            'failure_count' => $failureCount,
            'opened_at' => $openedAt,
        ]);
    }

    private function openWindowExpired(array $snapshot): bool
    {
        return $snapshot['opened_at'] !== null
            && now()->greaterThanOrEqualTo(Carbon::parse($snapshot['opened_at'])->addSeconds($this->openSeconds));
    }

    private function cacheKey(): string
    {
        return "circuit-breaker:{$this->name}";
    }
}
