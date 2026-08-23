<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAdminUserRequest;
use App\Http\Requests\Admin\UpdateAdminUserRequest;
use App\Models\Admin;
use App\Services\AuditLogger;
use App\Support\AjaxResponse;
use App\Support\PortalPermission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

class AdminUserController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function store(StoreAdminUserRequest $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validated();
        $admin = Admin::query()->create([
            'admin_code' => $this->nextAdminCode(),
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make((string) $validated['password']),
            'status' => $validated['status'],
            'otp_enabled' => false,
            'email_verified_at' => now(),
        ]);

        $admin->syncRoles($validated['roles']);
        $admin->syncPermissions($validated['permissions'] ?? []);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->auditLogger->record('admins.created', $request->user(), $request, $admin, [
            'roles' => $validated['roles'],
            'permissions' => $validated['permissions'] ?? [],
        ]);

        return AjaxResponse::success($request, 'Admin created.', reload: true);
    }

    public function update(UpdateAdminUserRequest $request, Admin $admin): JsonResponse|RedirectResponse
    {
        abort_if(PortalPermission::isRootAdmin($admin), 422, 'Super admin accounts cannot be edited here.');

        $validated = $request->validated();
        $before = [
            'name' => $admin->name,
            'email' => $admin->email,
            'status' => $admin->status,
            'roles' => $admin->roles->pluck('name')->all(),
            'permissions' => $admin->permissions->pluck('name')->all(),
        ];

        $admin->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'status' => $validated['status'],
            'otp_enabled' => false,
        ]);
        $admin->syncRoles($validated['roles']);
        $admin->syncPermissions($validated['permissions'] ?? []);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->auditLogger->record('admins.updated', $request->user(), $request, $admin, [
            'before' => $before,
            'after' => [
                'name' => $admin->name,
                'email' => $admin->email,
                'status' => $admin->status,
                'roles' => $validated['roles'],
                'permissions' => $validated['permissions'] ?? [],
            ],
        ]);

        return AjaxResponse::success($request, 'Admin updated.', reload: true);
    }

    private function nextAdminCode(): string
    {
        $nextId = (int) (Admin::query()->max('id') ?? 0) + 1;

        return 'ADM-'.str_pad((string) $nextId, 5, '0', STR_PAD_LEFT);
    }
}
