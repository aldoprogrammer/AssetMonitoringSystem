<?php

namespace App\Repositories\Eloquent;

use App\Models\Asset;
use App\Repositories\Contracts\AssetRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AssetRepository implements AssetRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Asset::query()->latest()->paginate($perPage);
    }

    public function create(array $attributes): Asset
    {
        return Asset::create($attributes);
    }

    public function update(Asset $asset, array $attributes): Asset
    {
        $asset->update($attributes);

        return $asset->refresh();
    }

    public function findOrFail(string $id): Asset
    {
        return Asset::query()->where('uuid', $id)->firstOrFail();
    }

    public function findBySerialNumber(string $serialNumber): Asset
    {
        return Asset::where('serial_number', $serialNumber)->firstOrFail();
    }
}
