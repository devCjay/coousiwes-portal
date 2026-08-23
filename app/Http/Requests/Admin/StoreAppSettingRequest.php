<?php

namespace App\Http\Requests\Admin;

use App\Support\PortalPermission;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAppSettingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return PortalPermission::userHas($this->user(), 'settings.update');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'group' => ['required', 'string', 'max:80'],
            'key' => ['required', 'string', 'max:120', Rule::unique('app_settings', 'key')->ignore($this->route('appSetting'))],
            'value' => ['nullable'],
            'type' => ['required', 'string', Rule::in(['string', 'integer', 'boolean', 'decimal', 'json', 'array'])],
            'is_public' => ['sometimes', 'boolean'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
