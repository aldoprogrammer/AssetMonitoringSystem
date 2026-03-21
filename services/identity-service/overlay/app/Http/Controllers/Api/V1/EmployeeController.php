<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Services\EmployeeService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

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

    public function show(int $employee): EmployeeResource
    {
        return EmployeeResource::make($this->employees->findOrFail($employee));
    }

    public function update(UpdateEmployeeRequest $request, int $employee): EmployeeResource
    {
        return EmployeeResource::make($this->employees->update($employee, $request->validated()));
    }
}
