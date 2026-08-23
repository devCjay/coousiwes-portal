<?php

namespace App\Http\Requests\Student;

use App\Models\AcademicSession;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePlacementStepRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('student') === true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return match ($this->input('step')) {
            'siwes' => [
                'step' => ['required', Rule::in(['siwes'])],
                'academic_level_id' => ['required', 'integer', Rule::exists('academic_levels', 'id')->whereNull('deleted_at')],
                'siwes_year' => ['required', 'integer', Rule::in([now()->year, now()->subYear()->year])],
                'academic_session_id' => ['required', 'integer', Rule::exists('academic_sessions', 'id')->whereNull('deleted_at')],
                'attachment_period' => ['required', Rule::in(['April to October', 'August to October'])],
            ],
            'company' => [
                'step' => ['required', Rule::in(['company'])],
                'company_name' => ['required', 'string', 'max:180'],
                'company_address' => ['required', 'string', 'max:1000'],
                'company_state' => ['required', 'string', 'max:120'],
                'company_lga' => ['required', 'string', 'max:120'],
                'company_supervisor_phone' => ['required', 'string', 'max:40'],
            ],
            default => [
                'step' => ['required', Rule::in(['siwes', 'company'])],
            ],
        };
    }

    #[\Override]
    protected function prepareForValidation(): void
    {
        if (! $this->filled('academic_session_id') && $this->input('step') === 'siwes') {
            $this->merge(['academic_session_id' => AcademicSession::active()?->id]);
        }
    }
}
