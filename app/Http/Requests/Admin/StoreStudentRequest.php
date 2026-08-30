<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\ReturnsAjaxValidationErrors;
use App\Models\Student;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentRequest extends FormRequest
{
    use ReturnsAjaxValidationErrors;

    public function authorize(): bool
    {
        return $this->user()?->can('students.create') === true;
    }

    protected function prepareForValidation(): void
    {
        $name = $this->input('name') ?: collect([
            $this->input('first_name'),
            $this->input('middle_name'),
            $this->input('last_name'),
        ])->filter()->join(' ');
        $matricNo = trim((string) $this->input('matric_no'));

        $this->merge([
            'name' => $name,
            'email' => $this->filled('email') ? $this->input('email') : null,
            'matric_no' => $matricNo,
            'faculty_id' => $this->filled('faculty_id') ? $this->input('faculty_id') : null,
            'department_id' => $this->filled('department_id') ? $this->input('department_id') : null,
            'academic_level_id' => $this->filled('academic_level_id') ? $this->input('academic_level_id') : null,
            'academic_session_id' => $this->filled('academic_session_id') ? $this->input('academic_session_id') : null,
            'activation_status' => $this->input('activation_status') ?: Student::STATUS_INACTIVE,
            'workshop_fee_paid' => $this->boolean('workshop_fee_paid'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['nullable', 'string', 'max:80'],
            'middle_name' => ['nullable', 'string', 'max:80'],
            'last_name' => ['nullable', 'string', 'max:80'],
            'name' => ['required', 'string', 'max:160'],
            'email' => ['nullable', 'email', 'max:160', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'string', 'max:40'],
            'matric_no' => ['required', 'string', 'max:40', Rule::unique('students', 'matric_no')],
            'faculty_id' => ['nullable', 'integer', Rule::exists('faculties', 'id')->whereNull('deleted_at')],
            'department_id' => [
                'nullable',
                'integer',
                Rule::exists('departments', 'id')
                    ->whereNull('deleted_at')
                    ->where('faculty_id', $this->integer('faculty_id')),
            ],
            'academic_level_id' => ['nullable', 'integer', Rule::exists('academic_levels', 'id')->whereNull('deleted_at')],
            'academic_session_id' => ['nullable', 'integer', Rule::exists('academic_sessions', 'id')->whereNull('deleted_at')],
            'activation_status' => ['required', 'string', Rule::in(['inactive', 'active', 'suspended'])],
            'workshop_fee_paid' => ['nullable', 'boolean'],
            'gender' => ['nullable', 'string', 'max:20'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'address' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'faculty_id.required' => 'No active faculty/department setup was found. Open System Settings and run Import / update database seeders.',
            'department_id.required' => 'No active department setup was found. Open System Settings and run Import / update database seeders.',
            'academic_level_id.required' => 'No active academic level setup was found. Open System Settings and run Import / update database seeders.',
            'academic_session_id.required' => 'No academic session setup was found. Open System Settings and run Import / update database seeders.',
        ];
    }

}
