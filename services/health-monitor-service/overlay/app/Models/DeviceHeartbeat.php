<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeviceHeartbeat extends Model
{
    use HasFactory;

    public const STATUS_ONLINE = 'online';
    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
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
}
