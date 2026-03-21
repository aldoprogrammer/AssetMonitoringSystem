<?php

namespace App\Services;

use App\Models\Asset;
use App\Repositories\Contracts\AssetRepositoryInterface;

class AssetService
{
    public function __construct(private readonly AssetRepositoryInterface $assets)
    {
    }

    public function paginate(int $perPage = 15)
    {
        return $this->assets->paginate($perPage);
    }

    public function findOrFail(int $id): Asset
    {
        return $this->assets->findOrFail($id);
    }

    public function create(array $payload): Asset
    {
        return $this->assets->create($payload);
    }

    public function update(int $id, array $payload): Asset
    {
        $asset = $this->assets->findOrFail($id);

        return $this->assets->update($asset, $payload);
    }

    public function validateAvailability(string $serialNumber): array
    {
        $asset = $this->assets->findBySerialNumber($serialNumber);

        return [
            'serial_number' => $asset->serial_number,
            'status' => $asset->status,
            'available' => $asset->isAvailable(),
        ];
    }
}
