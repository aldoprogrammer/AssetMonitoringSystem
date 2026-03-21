<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserProjection extends Model
{
    use HasFactory;

    protected $fillable = [
        'external_user_id',
        'employee_id',
        'name',
        'email',
        'role',
    ];
}
