<?php

namespace App\Repositories\Contracts;

use App\Models\UserProjection;

interface UserProjectionRepositoryInterface
{
    public function findByExternalId(string $externalUserId): ?UserProjection;

    public function updateOrCreateByExternalId(string $externalUserId, array $attributes): UserProjection;
}
