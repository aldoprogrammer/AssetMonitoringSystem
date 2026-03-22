<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'employee' => $this->whenLoaded('employee', fn () => [
                'id' => $this->employee?->uuid,
                'employee_code' => $this->employee?->employee_code,
                'department' => $this->employee?->department,
                'job_title' => $this->employee?->job_title,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
