<?php

namespace App\Http\Requests\Student;

use App\Http\Requests\Admin\Concerns\ReturnsAjaxValidationErrors;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileStepRequest extends FormRequest
{
    use ReturnsAjaxValidationErrors;

    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->hasRole('student') && $user->student !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $states = collect(config('siwes_profile.states', []))->pluck('name')->all();
        $banks = collect(config('siwes_profile.banks', []))->pluck('name')->all();
        $nationalities = collect(config('siwes_profile.nationalities', []))->pluck('label')->all();
        $selectedState = (string) $this->input('state');
        $lgas = collect(config('siwes_profile.states', []))
            ->firstWhere('name', $selectedState)['lgas'] ?? [];

        return match ((string) $this->input('step')) {
            'basic' => [
                'step' => ['required', Rule::in(['basic'])],
                'email' => ['required', 'email', 'max:160', Rule::unique('users', 'email')->ignore($this->user()->id)],
                'phone' => ['required', 'string', 'max:40'],
                'gender' => ['required', Rule::in(['Male', 'Female'])],
                'date_of_birth' => ['required', 'date', 'before:today'],
                'nationality' => ['required', 'string', Rule::in($nationalities)],
            ],
            'contact' => [
                'step' => ['required', Rule::in(['contact'])],
                'address' => ['required', 'string', 'max:1000'],
                'state' => ['required', 'string', Rule::in($states)],
                'lga' => ['required', 'string', Rule::in($lgas)],
            ],
            'academic' => [
                'step' => ['required', Rule::in(['academic'])],
                'faculty_id' => ['required', 'integer', Rule::exists('faculties', 'id')->whereNull('deleted_at')],
                'department_id' => [
                    'required',
                    'integer',
                    Rule::exists('departments', 'id')->whereNull('deleted_at')->where('faculty_id', $this->integer('faculty_id')),
                ],
            ],
            'bank' => [
                'step' => ['required', Rule::in(['bank'])],
                'bank_name' => ['required', 'string', Rule::in($banks)],
                'account_number' => ['required', 'digits:10'],
                'sort_code' => ['required', 'string', 'max:20'],
            ],
            default => [
                'step' => ['required', Rule::in(['basic', 'contact', 'academic', 'bank'])],
            ],
        };
    }
}
