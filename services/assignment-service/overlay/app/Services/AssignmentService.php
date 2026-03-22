<?php

namespace App\Services;

use App\Infrastructure\Messaging\TopicPublisher;
use App\Models\Assignment;
use App\Repositories\Contracts\AssignmentRepositoryInterface;
use App\Repositories\Contracts\UserProjectionRepositoryInterface;
use App\Support\Inventory\InventoryClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssignmentService
{
    public function __construct(
        private readonly AssignmentRepositoryInterface $assignments,
        private readonly UserProjectionRepositoryInterface $users,
        private readonly InventoryClient $inventoryClient,
        private readonly TopicPublisher $publisher,
    ) {
    }

    public function paginate(int $perPage = 15)
    {
        return $this->assignments->paginate($perPage);
    }

    public function checkout(array $payload): Assignment
    {
        $user = $this->users->findByExternalId((int) $payload['user_id']);

        if ($user === null) {
            throw ValidationException::withMessages([
                'user_id' => ['User projection is not synchronized yet.'],
            ]);
        }

        if ($this->assignments->activeBySerialNumber($payload['asset_serial_number']) !== null) {
            throw ValidationException::withMessages([
                'asset_serial_number' => ['Asset already has an active assignment.'],
            ]);
        }

        $inventoryStatus = $this->inventoryClient->assertAssetAvailable($payload['asset_serial_number']);

        $assignment = DB::transaction(function () use ($payload, $user): Assignment {
            return $this->assignments->create([
                'user_id' => $user->external_user_id,
                'user_email' => $user->email,
                'asset_serial_number' => $payload['asset_serial_number'],
                'status' => Assignment::STATUS_CHECKED_OUT,
                'checked_out_at' => now(),
            ]);
        });

        $this->publisher->publish('assignment.checked_out', [
            'message_id' => (string) str()->uuid(),
            'event_type' => 'assignment.checked_out',
            'occurred_at' => now()->toIso8601String(),
            'source_service' => 'assignment-service',
            'payload' => [
                'assignment_id' => $assignment->id,
                'assignment_public_id' => $assignment->uuid,
                'user_id' => $assignment->user_id,
                'user_email' => $assignment->user_email,
                'asset_serial_number' => $assignment->asset_serial_number,
                'inventory_source' => $inventoryStatus['source'] ?? 'live',
            ],
        ]);

        return $assignment;
    }

    public function checkin(int $id): Assignment
    {
        $assignment = $this->assignments->findOrFail($id);

        if ($assignment->status === Assignment::STATUS_CHECKED_IN) {
            return $assignment;
        }

        $assignment = $this->assignments->update($assignment, [
            'status' => Assignment::STATUS_CHECKED_IN,
            'checked_in_at' => now(),
        ]);

        $this->publisher->publish('assignment.checked_in', [
            'message_id' => (string) str()->uuid(),
            'event_type' => 'assignment.checked_in',
            'occurred_at' => now()->toIso8601String(),
            'source_service' => 'assignment-service',
            'payload' => [
                'assignment_id' => $assignment->id,
                'assignment_public_id' => $assignment->uuid,
                'user_id' => $assignment->user_id,
                'asset_serial_number' => $assignment->asset_serial_number,
            ],
        ]);

        return $assignment;
    }
}
