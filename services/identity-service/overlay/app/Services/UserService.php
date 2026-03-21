<?php

namespace App\Services;

use App\Infrastructure\Messaging\TopicPublisher;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;

class UserService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly TopicPublisher $publisher,
    ) {
    }

    public function paginate(int $perPage = 15)
    {
        return $this->users->paginate($perPage);
    }

    public function findOrFail(int $id): User
    {
        return $this->users->findOrFail($id);
    }

    public function create(array $payload): User
    {
        $user = $this->users->create($payload);
        $this->publishLifecycleEvent('user.created', $user);

        return $user;
    }

    public function update(int $id, array $payload): User
    {
        $user = $this->users->findOrFail($id);
        $user = $this->users->update($user, $payload);
        $this->publishLifecycleEvent('user.updated', $user);

        return $user;
    }

    private function publishLifecycleEvent(string $routingKey, User $user): void
    {
        $this->publisher->publish($routingKey, [
            'message_id' => (string) str()->uuid(),
            'event_type' => $routingKey,
            'occurred_at' => now()->toIso8601String(),
            'source_service' => 'identity-service',
            'payload' => [
                'id' => $user->id,
                'employee_id' => $user->employee_id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ]);
    }
}
