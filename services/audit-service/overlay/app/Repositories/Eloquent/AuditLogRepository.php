<?php

namespace App\Repositories\Eloquent;

use App\Models\AuditLog;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AuditLogRepository implements AuditLogRepositoryInterface
{
    public function paginate(int $perPage = 25): LengthAwarePaginator
    {
        return AuditLog::query()->latest('occurred_at')->paginate($perPage);
    }

    public function create(array $attributes): AuditLog
    {
        return AuditLog::create($attributes);
    }
}
