<?php

namespace App\Http\Middleware;

use App\Support\OtpRequirement;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureOtpIsVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('admin')->user() ?? Auth::guard('web')->user() ?? $request->user();

        if (! $user) {
            return redirect()->route('login.student');
        }

        if (OtpRequirement::requiredFor($user) && $request->session()->get('otp.verified') !== true) {
            return redirect()->route('otp.show');
        }

        return $next($request);
    }
}
