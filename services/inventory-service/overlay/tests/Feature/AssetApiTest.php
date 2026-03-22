<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
