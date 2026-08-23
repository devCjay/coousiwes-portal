<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\ReturnsAjaxValidationErrors;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssessmentRubricItemRequest extends FormRequest
{
    use ReturnsAjaxValidationErrors;

    public function authorize(): bool
    {
        return $this->user()?->can('settings.update') === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160', Rule::unique('assessment_rubric_items', 'name')->ignore($this->route('assessmentRubricItem'))],
            'description' => ['nullable', 'string', 'max:1000'],
            'max_score' => ['required', 'integer', 'min:1', 'max:100'],
            'weight' => ['required', 'integer', 'min:1', 'max:20'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
