<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Services\EmployeeService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EmployeeController extends Controller
{
    public function __construct(private readonly EmployeeService $employees)
    {
    }

    public function index(): AnonymousResourceCollection
    {
        return EmployeeResource::collection($this->employees->paginate());
    }

    public function store(StoreEmployeeRequest $request): EmployeeResource
    {
        return EmployeeResource::make($this->employees->create($request->validated()));
    }

    public function show(string $employee): EmployeeResource|JsonResponse
    {
        try {
            return EmployeeResource::make($this->employees->findOrFail($employee));
        } catch (ModelNotFoundException|NotFoundHttpException) {
            return $this->notFoundResponse('employee', 'ID', $employee);
        }
    }

    public function update(UpdateEmployeeRequest $request, string $employee): EmployeeResource|JsonResponse
    {
        try {
            return EmployeeResource::make($this->employees->update($employee, $request->validated()));
        } catch (ModelNotFoundException|NotFoundHttpException) {
            return $this->notFoundResponse('employee', 'ID', $employee);
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
