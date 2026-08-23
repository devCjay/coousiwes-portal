<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\ReturnsAjaxValidationErrors;
use App\Models\AcademicLevel;
use App\Models\AcademicSession;
use App\Models\Department;
use App\Models\Student;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class StoreStudentRequest extends FormRequest
{
    use ReturnsAjaxValidationErrors;

    public function authorize(): bool
    {
        return $this->user()?->can('students.create') === true;
    }

    protected function prepareForValidation(): void
    {
        $department = Department::query()
            ->with('faculty')
            ->where('is_active', true)
            ->orderBy('name')
            ->first();
        $level = AcademicLevel::query()
            ->where('is_active', true)
            ->orderBy('level')
            ->first();
        $session = AcademicSession::active()
            ?? AcademicSession::query()->orderByDesc('starts_on')->first();

        $name = $this->input('name') ?: collect([
            $this->input('first_name'),
            $this->input('middle_name'),
            $this->input('last_name'),
        ])->filter()->join(' ');
        $matricNo = trim((string) $this->input('matric_no'));
        $email = $this->input('email') ?: $this->generatedEmail($matricNo);

        $this->merge([
            'name' => $name,
            'email' => $email,
            'matric_no' => $matricNo,
            'faculty_id' => $this->input('faculty_id') ?: $department?->faculty?->id,
            'department_id' => $this->input('department_id') ?: $department?->id,
            'academic_level_id' => $this->input('academic_level_id') ?: $level?->id,
            'academic_session_id' => $this->input('academic_session_id') ?: $session?->id,
            'activation_status' => $this->input('activation_status') ?: Student::STATUS_ACTIVE,
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
            'email' => ['required', 'email', 'max:160', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'string', 'max:40'],
            'matric_no' => ['required', 'string', 'max:40', Rule::unique('students', 'matric_no')],
            'faculty_id' => ['required', 'integer', Rule::exists('faculties', 'id')->whereNull('deleted_at')],
            'department_id' => [
                'required',
                'integer',
                Rule::exists('departments', 'id')
                    ->whereNull('deleted_at')
                    ->where('faculty_id', $this->integer('faculty_id')),
            ],
            'academic_level_id' => ['required', 'integer', Rule::exists('academic_levels', 'id')->whereNull('deleted_at')],
            'academic_session_id' => ['required', 'integer', Rule::exists('academic_sessions', 'id')->whereNull('deleted_at')],
            'activation_status' => ['required', 'string', Rule::in(['inactive', 'active', 'suspended'])],
            'gender' => ['nullable', 'string', 'max:20'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'address' => ['nullable', 'string', 'max:1000'],
        ];
    }

    private function generatedEmail(string $matricNo): string
    {
        $slug = Str::of($matricNo)
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '.')
            ->trim('.');

        return ($slug->isNotEmpty() ? $slug : Str::random(10)).'@students.coousiwes.local';
    }
}
