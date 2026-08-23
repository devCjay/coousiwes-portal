<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\ReturnsAjaxValidationErrors;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateTicketsRequest extends FormRequest
{
    use ReturnsAjaxValidationErrors;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('tickets.generate') === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'quantity' => ['nullable', 'required_without:student_ids', 'integer', 'min:1', 'max:500'],
            'student_ids' => ['nullable', 'required_without:quantity', 'array', 'min:1'],
            'student_ids.*' => ['integer', Rule::exists('students', 'id')->whereNull('deleted_at')],
        ];
    }
}
