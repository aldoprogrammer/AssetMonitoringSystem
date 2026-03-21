<?php

namespace Tests\Unit;

use App\Enums\CircuitBreakerState;
use App\Support\CircuitBreaker\CircuitBreaker;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Tests\TestCase;

class CircuitBreakerTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_it_opens_after_reaching_the_failure_threshold(): void
    {
        Carbon::setTestNow('2026-03-21 10:00:00');

        $breaker = new CircuitBreaker(Cache::store('array'), 'inventory-service', 2, 30);
        $states = [];

        $fallback = function (CircuitBreakerState $state, ?\Throwable $exception = null) use (&$states): array {
            $states[] = $state->value;

            return ['available' => false];
        };

        $breaker->call(fn () => throw new RuntimeException('inventory down'), $fallback);
        $breaker->call(fn () => throw new RuntimeException('inventory down'), $fallback);
        $breaker->call(fn () => ['available' => true], $fallback);

        $this->assertSame(CircuitBreakerState::OPEN, $breaker->currentState());
        $this->assertSame(['closed', 'open', 'open'], $states);
    }

    public function test_it_moves_to_half_open_and_closes_after_a_successful_probe(): void
    {
        Carbon::setTestNow('2026-03-21 11:00:00');

        $breaker = new CircuitBreaker(Cache::store('array'), 'inventory-service', 2, 30);
        $fallback = fn (CircuitBreakerState $state, ?\Throwable $exception = null): array => ['state' => $state->value];

        $breaker->call(fn () => throw new RuntimeException('inventory down'), $fallback);
        $breaker->call(fn () => throw new RuntimeException('inventory down'), $fallback);

        Carbon::setTestNow('2026-03-21 11:00:31');

        $result = $breaker->call(
            fn (): array => ['available' => true],
            $fallback,
        );

        $this->assertSame(['available' => true], $result);
        $this->assertSame(CircuitBreakerState::CLOSED, $breaker->currentState());
    }
}
