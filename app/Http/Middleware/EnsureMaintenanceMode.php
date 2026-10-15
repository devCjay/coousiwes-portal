<?php

namespace App\Http\Middleware;

use App\Support\MaintenanceMode;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureMaintenanceMode
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! MaintenanceMode::enabled()) {
            return $next($request);
        }

        if (Auth::guard('admin')->check() || $this->hasStaffPortalSession()) {
            return $next($request);
        }

        $student = Auth::guard('web')->user();

        if ($student?->student()->exists()) {
            Auth::guard('web')->logout();

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            return redirect()->route('home');
        }

        if ($this->isStudentLoginAttempt($request)) {
            return redirect()->route('home');
        }

        return $next($request);
    }

    private function hasStaffPortalSession(): bool
    {
        $user = Auth::guard('web')->user();

        return $user !== null
            && ($user->supervisor()->exists() || $user->siwesCoordinator()->exists());
    }

    private function isStudentLoginAttempt(Request $request): bool
    {
        return $request->is('login/student');
    }
}
