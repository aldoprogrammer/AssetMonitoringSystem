<?php

namespace App\Repositories\Contracts;

use App\Models\Assignment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AssignmentRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function create(array $attributes): Assignment;

    public function findOrFail(string $id): Assignment;

    public function update(Assignment $assignment, array $attributes): Assignment;

    public function activeBySerialNumber(string $serialNumber): ?Assignment;
}
