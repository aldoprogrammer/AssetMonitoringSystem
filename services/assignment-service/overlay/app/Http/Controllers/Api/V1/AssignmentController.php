<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutAssignmentRequest;
use App\Http\Resources\AssignmentResource;
use App\Services\AssignmentService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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

    public function checkin(string $assignment): AssignmentResource|JsonResponse
    {
        try {
            return AssignmentResource::make($this->assignments->checkin($assignment));
        } catch (ModelNotFoundException|NotFoundHttpException) {
            return response()->json([
                'message' => "No assignment found with ID '{$assignment}'.",
                'error' => 'resource_not_found',
            ], 404);
        }
    }
}
