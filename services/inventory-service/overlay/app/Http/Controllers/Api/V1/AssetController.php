<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAssetRequest;
use App\Http\Requests\UpdateAssetRequest;
use App\Http\Resources\AssetResource;
use App\Services\AssetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AssetController extends Controller
{
    public function __construct(private readonly AssetService $assets)
    {
    }

    public function index(): AnonymousResourceCollection
    {
        return AssetResource::collection($this->assets->paginate());
    }

    public function store(StoreAssetRequest $request): AssetResource
    {
        return AssetResource::make($this->assets->create($request->validated()));
    }

    public function show(int $asset): AssetResource
    {
        return AssetResource::make($this->assets->findOrFail($asset));
    }

    public function update(UpdateAssetRequest $request, int $asset): AssetResource
    {
        return AssetResource::make($this->assets->update($asset, $request->validated()));
    }

    public function validateStatus(string $serialNumber): JsonResponse
    {
        return response()->json($this->assets->validateAvailability($serialNumber));
    }
}
