<?php

namespace App\Repositories\Contracts;

use App\Models\Asset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AssetRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function create(array $attributes): Asset;

    public function update(Asset $asset, array $attributes): Asset;

    public function findOrFail(string $id): Asset;

    public function findBySerialNumber(string $serialNumber): Asset;
}
