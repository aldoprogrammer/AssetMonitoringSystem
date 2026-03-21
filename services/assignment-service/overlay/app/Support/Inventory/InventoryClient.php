<?php

namespace App\Support\Inventory;

use App\Enums\CircuitBreakerState;
use App\Support\CircuitBreaker\CircuitBreaker;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Throwable;

class InventoryClient
{
    public function __construct(private readonly CircuitBreaker $circuitBreaker)
    {
    }

    public function assertAssetAvailable(string $serialNumber): array
    {
        $result = $this->circuitBreaker->call(
            function () use ($serialNumber): array {
                $response = Http::timeout(config('services.inventory.timeout'))
                    ->acceptJson()
                    ->get(config('services.inventory.base_url') . "/api/v1/assets/serial/{$serialNumber}/status");

                $response->throw();
                $payload = $response->json();
                Cache::put($this->cacheKey($serialNumber), $payload, now()->addMinutes(5));

                return $payload + ['source' => 'live'];
            },
            function (CircuitBreakerState $state, ?Throwable $exception) use ($serialNumber): array {
                $cached = Cache::get($this->cacheKey($serialNumber), [
                    'serial_number' => $serialNumber,
                    'status' => 'unavailable',
                    'available' => false,
                ]);

                return $cached + [
                    'source' => 'fallback',
                    'fallback_reason' => $state->value,
                    'error' => $exception?->getMessage(),
                ];
            },
        );

        if (! ($result['available'] ?? false)) {
            throw ValidationException::withMessages([
                'asset_serial_number' => [
                    'Asset cannot be assigned because inventory reported it as unavailable.',
                ],
            ]);
        }

        return $result;
    }

    private function cacheKey(string $serialNumber): string
    {
        return "inventory-status:{$serialNumber}";
    }
}
