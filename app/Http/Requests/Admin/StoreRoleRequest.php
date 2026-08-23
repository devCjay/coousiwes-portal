<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\ReturnsAjaxValidationErrors;
use App\Support\PortalPermission;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
{
    use ReturnsAjaxValidationErrors;

    public function authorize(): bool
    {
        $user = $this->user();

        return PortalPermission::isRootAdmin($user) && $user->can('roles.manage');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'current_password:admin'],
            'name' => ['required', 'string', 'max:80', Rule::unique('roles', 'name')->where('guard_name', 'web')->ignore($this->route('role'))],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['required', 'string', Rule::exists('permissions', 'name')->where('guard_name', 'web')],
        ];
    }
}
