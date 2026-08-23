<?php

namespace App\Http\Middleware;

use App\Support\PortalPermission;
use Closure;
use Illuminate\Http\Request;
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
            if (PortalPermission::userHas($user, $permissionName)) {
                return $next($request);
            }
        }

        abort(403, 'User does not have the right permissions.');
    }
}
