<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\ReturnsAjaxValidationErrors;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkAssignSupervisorRequest extends FormRequest
{
    use ReturnsAjaxValidationErrors;

    public function authorize(): bool
    {
        return $this->user()?->can('supervisors.assign') === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'supervisor_id' => ['required', 'integer', Rule::exists('supervisors', 'id')->whereNull('deleted_at')],
            'faculty_id' => ['nullable', 'integer', Rule::exists('faculties', 'id')->whereNull('deleted_at')],
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')->whereNull('deleted_at')],
            'academic_level_id' => ['nullable', 'integer', Rule::exists('academic_levels', 'id')->whereNull('deleted_at')],
            'academic_session_id' => ['nullable', 'integer', Rule::exists('academic_sessions', 'id')->whereNull('deleted_at')],
            'company_state' => ['nullable', 'string', 'max:120'],
            'company_lga' => ['nullable', 'string', 'max:120'],
        ];
    }
}
