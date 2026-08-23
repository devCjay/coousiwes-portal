<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\OtpChallenge;
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
        $challenge = $this->challenge($request);

        if (! $challenge || $challenge->isExpired()) {
            $role = RoleRedirector::roleSlugFor($request->user());

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login.'.$role)
                ->withErrors(['otp' => 'Your OTP challenge has expired. Please log in again.']);
        }

        return view('pages.auth.otp', [
            'debugCode' => session('otp.debug_code'),
            'expiresAt' => $challenge->expires_at,
        ]);
    }

    public function verify(Request $request, OtpService $otpService, AuditLogger $auditLogger): JsonResponse|RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $challenge = $this->challenge($request);

        if (! $challenge || ! $otpService->verify($challenge, $request->string('code')->toString())) {
            if ($request->user()) {
                $auditLogger->record('otp.verify_failed', $request->user(), $request);
            }

            return AjaxResponse::error($request, 'The OTP code is invalid or has expired.', key: 'code');
        }

        $auditLogger->record('otp.verify_success', $request->user(), $request);

        return AjaxResponse::success($request, 'OTP verified successfully.', RoleRedirector::dashboardFor($request->user()));
    }

    public function resend(Request $request, OtpService $otpService, AuditLogger $auditLogger): JsonResponse|RedirectResponse
    {
        $challenge = $otpService->createLoginChallenge($request->user(), $request);

        $auditLogger->record('otp.challenge_resent', $request->user(), $request, $challenge);

        return AjaxResponse::success($request, 'A new OTP code has been generated.');
    }

    private function challenge(Request $request): ?OtpChallenge
    {
        $challengeId = $request->session()->get('otp.challenge_id');

        if (! $challengeId || ! $request->user()) {
            return null;
        }

        return OtpChallenge::query()
            ->whereKey($challengeId)
            ->where('user_id', $request->user()->id)
            ->where('purpose', 'login')
            ->latest()
            ->first();
    }
}
