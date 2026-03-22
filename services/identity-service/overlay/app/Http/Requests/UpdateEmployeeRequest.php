<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $employeeId = \App\Models\Employee::query()
            ->where('uuid', (string) $this->route('employee'))
            ->value('id');

        return [
            'employee_code' => ['sometimes', 'string', 'max:50', Rule::unique('employees', 'employee_code')->ignore($employeeId)],
            'full_name' => ['sometimes', 'string', 'max:255'],
            'department' => ['sometimes', 'string', 'max:255'],
            'job_title' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('employees', 'email')->ignore($employeeId)],
        ];
    }
}
