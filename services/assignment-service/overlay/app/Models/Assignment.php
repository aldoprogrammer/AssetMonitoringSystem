<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Assignment extends Model
{
    use HasFactory;

    public const STATUS_CHECKED_OUT = 'checked_out';
    public const STATUS_CHECKED_IN = 'checked_in';

    protected $fillable = [
        'uuid',
        'user_id',
        'user_email',
        'asset_serial_number',
        'status',
        'checked_out_at',
        'checked_in_at',
    ];

    protected function casts(): array
    {
        return [
            'checked_out_at' => 'datetime',
            'checked_in_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $assignment): void {
            if (blank($assignment->uuid)) {
                $assignment->uuid = (string) Str::uuid();
            }
        });
    }
}
