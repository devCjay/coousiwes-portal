<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\ReturnsAjaxValidationErrors;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentRequest extends FormRequest
{
    use ReturnsAjaxValidationErrors;

    public function authorize(): bool
    {
        return $this->user()?->can('students.update') === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'email' => ['nullable', 'email', 'max:160', Rule::unique('users', 'email')->ignore($this->route('student')?->user_id)],
            'phone' => ['nullable', 'string', 'max:40'],
            'matric_no' => ['required', 'string', 'max:40', Rule::unique('students', 'matric_no')->ignore($this->route('student'))],
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
            'gender' => ['nullable', 'string', 'max:20'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'address' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
