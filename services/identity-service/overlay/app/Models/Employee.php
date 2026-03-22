<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_code',
        'full_name',
        'department',
        'job_title',
        'email',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $employee): void {
            if (blank($employee->uuid)) {
                $employee->uuid = (string) Str::uuid();
            }
        });
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }
}
