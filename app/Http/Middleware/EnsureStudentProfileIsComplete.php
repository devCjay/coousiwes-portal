<?php

namespace App\Http\Middleware;

use App\Models\Student;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStudentProfileIsComplete
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $student = $request->user()?->student;

        if ($student instanceof Student && ! $student->hasCompleteProfile()) {
            if (! $request->routeIs('student.profile.*')) {
                return redirect()
                    ->route('student.profile.edit')
                    ->with('toast_title', 'Profile update required')
                    ->with('toast_tone', 'warning')
                    ->with('status', 'Please upload your profile photo and complete every required field, including account name.');
            }
        }

        return $next($request);
    }
}
