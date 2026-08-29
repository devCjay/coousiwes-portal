<?php

namespace App\Http\Middleware;

use App\Models\Student;
use App\Support\PaymentSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWorkshopFeeIsPaid
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $student = $request->user()?->student;

        if ($student instanceof Student && ! PaymentSettings::studentHasPaidWorkshop($student)) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'message' => 'Workshop fee payment is required before accessing placement.',
                    'redirect' => route('student.workshop.checkout', absolute: false),
                ], 422);
            }

            return redirect()->route('student.workshop.checkout')
                ->with('status', 'Workshop fee payment is required before accessing placement.');
        }

        return $next($request);
    }
}
