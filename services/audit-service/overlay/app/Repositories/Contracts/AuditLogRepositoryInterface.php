<?php

namespace App\Repositories\Contracts;

use App\Models\AuditLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AuditLogRepositoryInterface
{
    public function paginate(int $perPage = 25): LengthAwarePaginator;

    public function create(array $attributes): AuditLog;
}
