<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAssetRequest;
use App\Http\Requests\UpdateAssetRequest;
use App\Http\Resources\AssetResource;
use App\Services\AssetService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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

    public function show(string $asset): AssetResource|JsonResponse
    {
        try {
            return AssetResource::make($this->assets->findOrFail($asset));
        } catch (ModelNotFoundException|NotFoundHttpException) {
            return $this->notFoundResponse('asset', 'ID', $asset);
        }
    }

    public function update(UpdateAssetRequest $request, string $asset): AssetResource|JsonResponse
    {
        try {
            return AssetResource::make($this->assets->update($asset, $request->validated()));
        } catch (ModelNotFoundException|NotFoundHttpException) {
            return $this->notFoundResponse('asset', 'ID', $asset);
        }
    }

    public function validateStatus(string $serialNumber): JsonResponse
    {
        try {
            return response()->json($this->assets->validateAvailability($serialNumber));
        } catch (ModelNotFoundException|NotFoundHttpException) {
            return $this->notFoundResponse('asset', 'serial number', $serialNumber);
        }
    }

    private function notFoundResponse(string $resource, string $lookupLabel, string $lookupValue): JsonResponse
    {
        return response()->json([
            'message' => "No {$resource} found with {$lookupLabel} '{$lookupValue}'.",
            'error' => 'resource_not_found',
        ], 404);
    }
}
