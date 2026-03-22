<?php

namespace App\Repositories\Eloquent;

use App\Models\UserProjection;
use App\Repositories\Contracts\UserProjectionRepositoryInterface;

class UserProjectionRepository implements UserProjectionRepositoryInterface
{
    public function findByExternalId(string $externalUserId): ?UserProjection
    {
        return UserProjection::query()->where('external_user_id', $externalUserId)->first();
    }

    public function updateOrCreateByExternalId(string $externalUserId, array $attributes): UserProjection
    {
        return UserProjection::query()->updateOrCreate(
            ['external_user_id' => $externalUserId],
            $attributes,
        );
    }
}
