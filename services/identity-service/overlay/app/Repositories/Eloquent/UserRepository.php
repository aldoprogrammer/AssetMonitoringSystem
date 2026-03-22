<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserRepository implements UserRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return User::with('employee')->latest()->paginate($perPage);
    }

    public function create(array $attributes): User
    {
        return User::create($attributes)->load('employee');
    }

    public function update(User $user, array $attributes): User
    {
        $user->update($attributes);

        return $user->refresh()->load('employee');
    }

    public function findOrFail(string $id): User
    {
        return User::with('employee')->where('uuid', $id)->firstOrFail();
    }
}
