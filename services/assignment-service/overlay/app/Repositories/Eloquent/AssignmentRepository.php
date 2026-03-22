<?php

namespace App\Repositories\Eloquent;

use App\Models\Assignment;
use App\Repositories\Contracts\AssignmentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AssignmentRepository implements AssignmentRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Assignment::query()->latest()->paginate($perPage);
    }

    public function create(array $attributes): Assignment
    {
        return Assignment::create($attributes);
    }

    public function findOrFail(string $id): Assignment
    {
        return Assignment::query()->where('uuid', $id)->firstOrFail();
    }

    public function update(Assignment $assignment, array $attributes): Assignment
    {
        $assignment->update($attributes);

        return $assignment->refresh();
    }

    public function activeBySerialNumber(string $serialNumber): ?Assignment
    {
        return Assignment::query()
            ->where('asset_serial_number', $serialNumber)
            ->where('status', Assignment::STATUS_CHECKED_OUT)
            ->first();
    }
}
