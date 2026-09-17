<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\ReturnsAjaxValidationErrors;
use App\Models\Supervisor;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSupervisorRequest extends FormRequest
{
    use ReturnsAjaxValidationErrors;

    public function authorize(): bool
    {
        return $this->user()?->can('supervisors.update') === true;
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
            'email' => ['required', 'email', 'max:160', Rule::unique('users', 'email')->ignore($this->route('supervisor')?->user_id)],
            'phone' => ['nullable', 'string', 'max:40'],
            'staff_no' => ['required', 'string', 'max:40', Rule::unique('supervisors', 'staff_no')->ignore($this->route('supervisor'))],
            'organization' => ['nullable', 'string', 'max:160'],
            'faculty_id' => ['nullable', 'integer', Rule::exists('faculties', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'department_id' => ['required', 'integer', Rule::exists('departments', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'rank' => ['required', 'string', Rule::in(Supervisor::RANKS)],
        ];
    }
}
