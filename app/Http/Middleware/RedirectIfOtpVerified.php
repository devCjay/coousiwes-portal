<?php

namespace App\Http\Middleware;

use App\Support\OtpRequirement;
use App\Support\RoleRedirector;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfOtpVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('admin')->user() ?? Auth::guard('web')->user() ?? $request->user();

        if ($user && (! OtpRequirement::requiredFor($user) || $request->session()->get('otp.verified') === true)) {
            return redirect()->to(RoleRedirector::dashboardFor($user));
        }

        return $next($request);
    }
}
