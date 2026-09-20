<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Services\OtpService;
use App\Support\AjaxResponse;
use App\Support\RoleRedirector;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OtpChallengeController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        $user = $this->authenticatedUser($request);
        $otpService = app(OtpService::class);
        $expiresAt = $user ? $otpService->expiresAt($user, $request) : null;

        if (! $user || ! $expiresAt || $expiresAt->isPast()) {
            $role = $user ? RoleRedirector::roleSlugFor($user) : 'student';

            Auth::guard('web')->logout();
            Auth::guard('admin')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login.'.$role)
                ->withErrors(['otp' => 'Your OTP challenge has expired. Please log in again.']);
        }

        return view('pages.auth.otp', [
            'debugCode' => session('otp.debug_code'),
            'expiresAt' => $expiresAt,
        ]);
    }

    public function verify(Request $request, OtpService $otpService, AuditLogger $auditLogger): JsonResponse|RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $user = $this->authenticatedUser($request);

        if (! $user || ! $otpService->verifyLoginChallenge($user, $request, $request->string('code')->toString())) {
            if ($user) {
                $auditLogger->record('otp.verify_failed', $user, $request);
            }

            return AjaxResponse::error($request, 'The OTP code is invalid or has expired.', key: 'code');
        }

        $auditLogger->record('otp.verify_success', $user, $request);

        return AjaxResponse::success($request, 'OTP verified successfully.', RoleRedirector::dashboardFor($user));
    }

    public function resend(Request $request, OtpService $otpService, AuditLogger $auditLogger): JsonResponse|RedirectResponse
    {
        $user = $this->authenticatedUser($request);
        abort_unless($user !== null, 403);

        $challenge = $otpService->createLoginChallenge($user, $request);

        $auditLogger->record('otp.challenge_resent', $user, $request, $challenge);

        return AjaxResponse::success($request, 'A new OTP code has been generated.');
    }

    private function authenticatedUser(Request $request): \Illuminate\Contracts\Auth\Authenticatable|null
    {
        return Auth::guard('admin')->user() ?? Auth::guard('web')->user() ?? $request->user();
    }
}
