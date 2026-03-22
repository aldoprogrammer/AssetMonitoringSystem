<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AssetApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_asset_can_be_created_listed_and_validated_by_serial_number(): void
    {
        $createResponse = $this->postJson(route('assets.store', absolute: false), [
            'serial_number' => 'LAP-1001',
            'asset_tag' => 'AST-1001',
            'specs' => [
                'cpu' => 'Intel Core i7',
                'ram' => '16GB',
            ],
            'status' => 'available',
        ]);

        $createResponse
            ->assertOk()
            ->assertJsonPath('data.serial_number', 'LAP-1001');

        $this->getJson(route('assets.index', absolute: false))
            ->assertOk()
            ->assertJsonPath('data.0.serial_number', 'LAP-1001');

        $this->getJson('/api/v1/assets/serial/LAP-1001/status')
            ->assertOk()
            ->assertJson([
                'serial_number' => 'LAP-1001',
                'status' => 'available',
                'available' => true,
            ]);
    }

    public function test_lookup_endpoints_return_human_friendly_not_found_messages(): void
    {
        $missingAssetId = (string) Str::uuid();

        $this->getJson(route('assets.show', ['asset' => $missingAssetId], absolute: false))
            ->assertNotFound()
            ->assertExactJson([
                'message' => "No asset found with ID '{$missingAssetId}'.",
                'error' => 'resource_not_found',
            ]);

        $this->getJson('/api/v1/assets/serial/UNKNOWN-404/status')
            ->assertNotFound()
            ->assertExactJson([
                'message' => "No asset found with serial number 'UNKNOWN-404'.",
                'error' => 'resource_not_found',
            ]);
    }
}
