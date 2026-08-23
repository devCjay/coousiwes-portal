<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasPermission
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();
        $permissions = collect(explode('|', $permission))
            ->flatMap(fn (string $value) => explode(',', $value))
            ->map(fn (string $value) => trim($value))
            ->filter()
            ->values();

        abort_unless($user && $permissions->isNotEmpty(), 403);

        foreach ($permissions as $permissionName) {
            if ($user->can($permissionName) || $this->hasPermissionInDatabase($user, $permissionName)) {
                return $next($request);
            }
        }

        abort(403, 'User does not have the right permissions.');
    }

    private function hasPermissionInDatabase(object $user, string $permission): bool
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
}
