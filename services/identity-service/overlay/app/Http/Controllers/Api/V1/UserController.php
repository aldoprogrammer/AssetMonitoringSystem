<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Services\UserService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserController extends Controller
{
    public function __construct(private readonly UserService $users)
    {
    }

    public function index(): AnonymousResourceCollection
    {
        return UserResource::collection($this->users->paginate());
    }

    public function store(StoreUserRequest $request): UserResource
    {
        return UserResource::make($this->users->create($request->validated()));
    }

    public function show(string $user): UserResource|JsonResponse
    {
        try {
            return UserResource::make($this->users->findOrFail($user));
        } catch (ModelNotFoundException|NotFoundHttpException) {
            return $this->notFoundResponse('user', 'ID', $user);
        }
    }

    public function update(UpdateUserRequest $request, string $user): UserResource|JsonResponse
    {
        try {
            return UserResource::make($this->users->update($user, $request->validated()));
        } catch (ModelNotFoundException|NotFoundHttpException) {
            return $this->notFoundResponse('user', 'ID', $user);
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
