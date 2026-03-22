<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Asset extends Model
{
    use HasFactory;

    public const STATUS_AVAILABLE = 'available';
    public const STATUS_ASSIGNED = 'assigned';
    public const STATUS_MAINTENANCE = 'maintenance';
    public const STATUS_RETIRED = 'retired';

    protected $fillable = [
        'uuid',
        'serial_number',
        'asset_tag',
        'specs',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'specs' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $asset): void {
            if (blank($asset->uuid)) {
                $asset->uuid = (string) Str::uuid();
            }
        });
    }

    public function isAvailable(): bool
    {
        return $this->status === self::STATUS_AVAILABLE;
    }
}
