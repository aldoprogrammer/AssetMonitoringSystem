<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DeviceHeartbeat extends Model
{
    use HasFactory;

    public const STATUS_ONLINE = 'online';
    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'uuid',
        'device_serial_number',
        'status',
        'metadata',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'last_seen_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $heartbeat): void {
            if (blank($heartbeat->uuid)) {
                $heartbeat->uuid = (string) Str::uuid();
            }
        });
    }
}
