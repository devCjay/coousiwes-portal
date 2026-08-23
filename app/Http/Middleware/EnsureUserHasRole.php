<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user instanceof Admin && in_array('admin', $roles, true) && $user->status === Admin::STATUS_ACTIVE) {
            return $next($request);
        }

        if (
            $user instanceof \App\Models\User
            && in_array('supervisor', $roles, true)
            && $user->supervisor()->where('status', \App\Models\Supervisor::STATUS_ACTIVE)->exists()
        ) {
            return $next($request);
        }

        if (
            $user instanceof \App\Models\User
            && in_array('student', $roles, true)
            && $user->student()->exists()
        ) {
            return $next($request);
        }

        abort_unless($user && $user->hasAnyRole($roles), 403);

        return $next($request);
    }
}
