<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRoleRequest;
use App\Services\AuditLogger;
use App\Support\AjaxResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleManagementController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function store(StoreRoleRequest $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validated();
        $role = new Role(['name' => $validated['name'], 'guard_name' => 'web']);
        $role->save();
        $role->syncPermissions($validated['permissions']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->auditLogger->record('roles.created', $request->user(), $request, $role, [
            'permissions' => $validated['permissions'],
        ]);

        return AjaxResponse::success($request, 'Role created.', reload: true);
    }

    public function update(StoreRoleRequest $request, Role $role): JsonResponse|RedirectResponse
    {
        abort_if($role->name === 'super-admin', 422, 'The super-admin role cannot be edited here.');

        $validated = $request->validated();
        $before = ['name' => $role->name, 'permissions' => $role->permissions->pluck('name')->all()];
        $role->update(['name' => $validated['name']]);
        $role->syncPermissions($validated['permissions']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->auditLogger->record('roles.updated', $request->user(), $request, $role, [
            'before' => $before,
            'after' => ['name' => $role->name, 'permissions' => $validated['permissions']],
        ]);

        return AjaxResponse::success($request, 'Role updated.', reload: true);
    }
}
