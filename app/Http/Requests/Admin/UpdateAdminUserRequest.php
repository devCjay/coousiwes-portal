<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\ReturnsAjaxValidationErrors;
use App\Support\PortalPermission;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdminUserRequest extends FormRequest
{
    use ReturnsAjaxValidationErrors;

    public function authorize(): bool
    {
        $user = $this->user();

        return PortalPermission::isRootAdmin($user) && $user->can('admins.manage');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'current_password:admin'],
            'name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:190', Rule::unique('admins', 'email')->ignore($this->route('admin'))],
            'phone' => ['nullable', 'string', 'max:40'],
            'status' => ['required', 'string', Rule::in(['active', 'inactive', 'suspended'])],
            'otp_enabled' => ['sometimes', 'boolean'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['required', 'string', Rule::exists('roles', 'name')->where('guard_name', 'web'), Rule::notIn(['super-admin', 'student', 'supervisor'])],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['required', 'string', Rule::exists('permissions', 'name')->where('guard_name', 'web')],
        ];
    }
}
