<?php

namespace App\Http\Middleware;

use App\Support\RoleRedirector;
use Closure;
use Illuminate\Http\Request;
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
        if ($request->user()) {
            return redirect()->to(RoleRedirector::dashboardFor($request->user()));
        }

        return $next($request);
    }
}
