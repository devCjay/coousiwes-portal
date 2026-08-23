<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDepartmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('academics.manage') === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'faculty_id' => ['required', 'integer', Rule::exists('faculties', 'id')->whereNull('deleted_at')],
            'name' => [
                'required',
                'string',
                'max:160',
                Rule::unique('departments', 'name')
                    ->where('faculty_id', $this->integer('faculty_id'))
                    ->ignore($this->route('department')),
            ],
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('departments', 'code')
                    ->where('faculty_id', $this->integer('faculty_id'))
                    ->ignore($this->route('department')),
            ],
            'is_active' => ['sometimes', 'boolean'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
