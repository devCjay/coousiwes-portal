<?php

namespace App\Support;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PortalPermission
{
    public static function gateBefore(object $user, string $permission): ?bool
    {
        if (! $user instanceof Admin) {
            return null;
        }

        if (self::isRootAdmin($user)) {
            return true;
        }

        if ($user->status !== Admin::STATUS_ACTIVE) {
            return false;
        }

        if ($permission === 'dashboard.view') {
            return true;
        }

        return self::hasPermissionInDatabase($user, $permission, ignoreBaseAdminRole: true);
    }

    public static function userHas(object|null $user, string $permission): bool
    {
        if (! $user) {
            return false;
        }

        if (method_exists($user, 'can') && $user->can($permission)) {
            return true;
        }

        if (self::hasPermissionInDatabase($user, $permission, ignoreBaseAdminRole: $user instanceof Admin)) {
            return true;
        }

        return self::hasFallbackPermission($user, $permission);
    }

    public static function hasFallbackPermission(object|null $user, string $permission): bool
    {
        return self::isRootAdmin($user);
    }

    public static function isRootAdmin(object|null $admin): bool
    {
        if (! $admin instanceof Admin || $admin->status !== Admin::STATUS_ACTIVE) {
            return false;
        }

        return $admin->admin_code === 'ADM-00001'
            || $admin->email === 'superadmin@coousiwes.test'
            || self::hasRoleInDatabase($admin, 'super-admin');
    }

    private static function hasPermissionInDatabase(object $user, string $permission, bool $ignoreBaseAdminRole = false): bool
    {
        $modelType = match (true) {
            $user instanceof Admin => Admin::class,
            $user instanceof User => User::class,
            default => null,
        };

        if ($modelType === null) {
            return false;
        }

        $modelId = (int) $user->getKey();

        $hasDirectPermission = DB::table('model_has_permissions')
            ->join('permissions', 'permissions.id', '=', 'model_has_permissions.permission_id')
            ->where('model_has_permissions.model_type', $modelType)
            ->where('model_has_permissions.model_id', $modelId)
            ->where('permissions.name', $permission)
            ->where('permissions.guard_name', 'web')
            ->exists();

        if ($hasDirectPermission) {
            return true;
        }

        $roleQuery = DB::table('model_has_roles')
            ->join('role_has_permissions', 'role_has_permissions.role_id', '=', 'model_has_roles.role_id')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
            ->where('model_has_roles.model_type', $modelType)
            ->where('model_has_roles.model_id', $modelId)
            ->where('permissions.name', $permission)
            ->where('permissions.guard_name', 'web');

        if ($ignoreBaseAdminRole && $user instanceof Admin) {
            $roleQuery->where('roles.name', '!=', 'admin');
        }

        return $roleQuery->exists();
    }

    private static function hasRoleInDatabase(Admin $admin, string $role): bool
    {
        return DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_type', Admin::class)
            ->where('model_has_roles.model_id', (int) $admin->getKey())
            ->where('roles.name', $role)
            ->where('roles.guard_name', 'web')
            ->exists();
    }
}
