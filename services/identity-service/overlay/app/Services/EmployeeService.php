<?php

namespace App\Services;

use App\Models\Employee;
use App\Repositories\Contracts\EmployeeRepositoryInterface;

class EmployeeService
{
    public function __construct(private readonly EmployeeRepositoryInterface $employees)
    {
    }

    public function paginate(int $perPage = 15)
    {
        return $this->employees->paginate($perPage);
    }

    public function findOrFail(int $id): Employee
    {
        return $this->employees->findOrFail($id);
    }

    public function create(array $payload): Employee
    {
        return $this->employees->create($payload);
    }

    public function update(int $id, array $payload): Employee
    {
        $employee = $this->employees->findOrFail($id);

        return $this->employees->update($employee, $payload);
    }
}
