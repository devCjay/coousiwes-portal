<?php

namespace App\Support;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PortalPermission
{
    /**
     * @var list<string>
     */
    private const ADMIN_BASELINE_PERMISSIONS = [
        'dashboard.view',
        'students.view',
        'students.create',
        'students.update',
        'students.suspend',
        'students.import',
        'students.export',
        'tickets.view',
        'tickets.generate',
        'tickets.revoke',
        'supervisors.view',
        'supervisors.create',
        'supervisors.update',
        'supervisors.suspend',
        'supervisors.assign',
        'feedback.view',
        'feedback.manage',
        'payments.view',
        'payments.export',
        'academics.manage',
        'settings.view',
        'settings.update',
        'notifications.manage',
    ];

    public static function userHas(object|null $user, string $permission): bool
    {
        if (! $user) {
            return false;
        }

        if (method_exists($user, 'can') && $user->can($permission)) {
            return true;
        }

        if (self::hasPermissionInDatabase($user, $permission)) {
            return true;
        }

        return self::hasFallbackPermission($user, $permission);
    }

    public static function hasFallbackPermission(object|null $user, string $permission): bool
    {
        if (! $user) {
            return false;
        }

        return self::hasBaselineAdminPermission($user, $permission);
    }

    private static function hasBaselineAdminPermission(object $user, string $permission): bool
    {
        if (! $user instanceof Admin || $user->status !== Admin::STATUS_ACTIVE) {
            return false;
        }

        if (self::isRootAdmin($user)) {
            return true;
        }

        return in_array($permission, self::ADMIN_BASELINE_PERMISSIONS, true);
    }

    private static function isRootAdmin(Admin $admin): bool
    {
        return $admin->admin_code === 'ADM-00001'
            || $admin->email === 'superadmin@coousiwes.test'
            || self::hasRoleInDatabase($admin, 'super-admin');
    }

    private static function hasPermissionInDatabase(object $user, string $permission): bool
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

        return DB::table('model_has_roles')
            ->join('role_has_permissions', 'role_has_permissions.role_id', '=', 'model_has_roles.role_id')
            ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
            ->where('model_has_roles.model_type', $modelType)
            ->where('model_has_roles.model_id', $modelId)
            ->where('permissions.name', $permission)
            ->where('permissions.guard_name', 'web')
            ->exists();
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
