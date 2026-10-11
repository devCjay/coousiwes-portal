<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\ReturnsAjaxValidationErrors;
use App\Models\SiwesCoordinator;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCoordinatorRequest extends FormRequest
{
    use ReturnsAjaxValidationErrors;

    public function authorize(): bool
    {
        return $this->user()?->can('coordinators.create') === true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:160', Rule::unique('users', 'email')],
            'phone' => ['required', 'string', 'max:40'],
            'faculty_id' => ['required', 'integer', Rule::exists('faculties', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'department_id' => ['required', 'integer', Rule::exists('departments', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'rank' => ['required', 'string', Rule::in(SiwesCoordinator::RANKS)],
        ];
    }
}
