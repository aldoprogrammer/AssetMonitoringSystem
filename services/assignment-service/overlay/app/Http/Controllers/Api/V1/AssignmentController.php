<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutAssignmentRequest;
use App\Http\Resources\AssignmentResource;
use App\Services\AssignmentService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AssignmentController extends Controller
{
    public function __construct(private readonly AssignmentService $assignments)
    {
    }

    public function index(): AnonymousResourceCollection
    {
        return AssignmentResource::collection($this->assignments->paginate());
    }

    public function checkout(CheckoutAssignmentRequest $request): AssignmentResource
    {
        return AssignmentResource::make($this->assignments->checkout($request->validated()));
    }

    public function checkin(int $assignment): AssignmentResource
    {
        return AssignmentResource::make($this->assignments->checkin($assignment));
    }
}
