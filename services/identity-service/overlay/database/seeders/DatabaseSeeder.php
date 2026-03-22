<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $employee = Employee::query()->firstOrCreate(
            ['employee_code' => 'EMP-001'],
            [
                'full_name' => 'System Administrator',
                'department' => 'IT Operations',
                'job_title' => 'IT Administrator',
                'email' => 'admin@assetmonitoringsystem.local',
            ],
        );

        User::query()->firstOrCreate(
            ['email' => 'admin@assetmonitoringsystem.local'],
            [
                'employee_id' => $employee->id,
                'name' => 'AssetMonitoringSystem Admin',
                'password' => 'AdminPass123!',
                'role' => User::ROLE_ADMIN,
            ],
        );
    }
}
