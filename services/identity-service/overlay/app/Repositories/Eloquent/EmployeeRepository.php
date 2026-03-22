<?php

namespace App\Repositories\Eloquent;

use App\Models\Employee;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EmployeeRepository implements EmployeeRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Employee::latest()->paginate($perPage);
    }

    public function create(array $attributes): Employee
    {
        return Employee::create($attributes);
    }

    public function update(Employee $employee, array $attributes): Employee
    {
        $employee->update($attributes);

        return $employee->refresh();
    }

    public function findOrFail(string $id): Employee
    {
        return Employee::where('uuid', $id)->firstOrFail();
    }

    public function findByUuidOrFail(string $uuid): Employee
    {
        return Employee::where('uuid', $uuid)->firstOrFail();
    }
}
