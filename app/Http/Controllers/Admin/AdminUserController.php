<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAdminUserRequest;
use App\Http\Requests\Admin\UpdateAdminUserRequest;
use App\Models\User;
use App\Services\AuditLogger;
use App\Support\AjaxResponse;
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
        $admin = User::query()->create([
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

    public function update(UpdateAdminUserRequest $request, User $user): JsonResponse|RedirectResponse
    {
        abort_if($user->hasRole('super-admin'), 422, 'Super admin accounts cannot be edited here.');

        $validated = $request->validated();
        $before = [
            'name' => $user->name,
            'email' => $user->email,
            'status' => $user->status,
            'roles' => $user->roles->pluck('name')->all(),
            'permissions' => $user->permissions->pluck('name')->all(),
        ];

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'status' => $validated['status'],
            'otp_enabled' => false,
        ]);
        $user->syncRoles($validated['roles']);
        $user->syncPermissions($validated['permissions'] ?? []);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->auditLogger->record('admins.updated', $request->user(), $request, $user, [
            'before' => $before,
            'after' => [
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->status,
                'roles' => $validated['roles'],
                'permissions' => $validated['permissions'] ?? [],
            ],
        ]);

        return AjaxResponse::success($request, 'Admin updated.', reload: true);
    }
}
