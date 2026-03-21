<?php

namespace App\Services;

use App\Repositories\Contracts\UserProjectionRepositoryInterface;

class UserSyncService
{
    public function __construct(private readonly UserProjectionRepositoryInterface $users)
    {
    }

    public function sync(array $payload): void
    {
        $this->users->updateOrCreateByExternalId((int) $payload['id'], [
            'employee_id' => $payload['employee_id'] ?? null,
            'name' => $payload['name'],
            'email' => $payload['email'],
            'role' => $payload['role'],
        ]);
    }
}
