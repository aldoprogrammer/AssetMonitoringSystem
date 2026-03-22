<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class NotificationDelivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'event_type',
        'recipient',
        'channel',
        'status',
        'payload',
        'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'delivered_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $delivery): void {
            if (blank($delivery->uuid)) {
                $delivery->uuid = (string) Str::uuid();
            }
        });
    }
}
