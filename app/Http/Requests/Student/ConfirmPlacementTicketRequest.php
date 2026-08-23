<?php

namespace App\Http\Requests\Student;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ConfirmPlacementTicketRequest extends FormRequest
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
        return [
            'serial_number' => ['required', 'string', 'max:32'],
            'pin' => ['required', 'string', 'max:20'],
        ];
    }
}
