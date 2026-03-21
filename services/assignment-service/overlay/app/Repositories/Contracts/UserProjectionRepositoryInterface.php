<?php

namespace App\Repositories\Contracts;

use App\Models\UserProjection;

interface UserProjectionRepositoryInterface
{
    public function findByExternalId(int $externalUserId): ?UserProjection;

    public function updateOrCreateByExternalId(int $externalUserId, array $attributes): UserProjection;
}
