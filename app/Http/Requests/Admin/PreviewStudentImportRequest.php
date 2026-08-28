<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\ReturnsAjaxValidationErrors;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class PreviewStudentImportRequest extends FormRequest
{
    use ReturnsAjaxValidationErrors;

    public function authorize(): bool
    {
        return $this->user()?->can('students.import') === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'students_file' => ['required', 'file', 'max:10240', 'mimes:csv,txt,xlsx'],
            'auto_activate' => ['nullable', 'boolean'],
        ];
    }
}
