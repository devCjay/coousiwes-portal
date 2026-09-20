<?php

namespace App\Http\Middleware;

use App\Models\Supervisor;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSupervisorProfileIsComplete
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $supervisor = $request->user()?->supervisor;

        if ($supervisor instanceof Supervisor && ! $this->hasBankDetails($supervisor)) {
            if (! $request->routeIs('supervisor.profile.*')) {
                return redirect()
                    ->route('supervisor.profile.edit')
                    ->with('toast_title', 'Profile update required')
                    ->with('toast_tone', 'warning')
                    ->with('status', 'Please complete your supervisor bank details before accessing the dashboard.');
            }
        }

        return $next($request);
    }

    private function hasBankDetails(Supervisor $supervisor): bool
    {
        $metadata = $supervisor->metadata ?? [];

        foreach (['bank_name', 'account_number', 'account_name', 'sort_code'] as $field) {
            if (! filled($metadata[$field] ?? null)) {
                return false;
            }
        }

        return true;
    }
}
