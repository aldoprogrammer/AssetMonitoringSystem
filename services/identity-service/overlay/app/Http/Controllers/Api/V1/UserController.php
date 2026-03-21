<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Services\UserService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

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

    public function show(int $user): UserResource
    {
        return UserResource::make($this->users->findOrFail($user));
    }

    public function update(UpdateUserRequest $request, int $user): UserResource
    {
        return UserResource::make($this->users->update($user, $request->validated()));
    }
}
