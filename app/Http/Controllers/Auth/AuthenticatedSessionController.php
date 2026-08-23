<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Student;
use App\Models\User;
use App\Services\AuditLogger;
use App\Support\AjaxResponse;
use App\Support\RoleRedirector;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(string $role): View
    {
        abort_unless(in_array($role, ['admin', 'supervisor', 'student'], true), 404);

        return view('pages.auth.login', ['role' => ucfirst($role)]);
    }

    public function store(Request $request, string $role, AuditLogger $auditLogger): JsonResponse|RedirectResponse
    {
        abort_unless(in_array($role, ['admin', 'supervisor', 'student'], true), 404);

        $credentials = $this->credentials($request, $role);

        $guard = $role === 'admin' ? 'admin' : 'web';

        if (! Auth::guard($guard)->attempt($credentials, $request->boolean('remember'))) {
            $auditLogger->record('auth.login_failed', null, $request, metadata: [
                'identifier' => $role === 'student'
                    ? $request->string('matric_no')->toString()
                    : $request->string('email')->lower()->toString(),
                'portal' => $role,
            ]);

            throw ValidationException::withMessages([
                $this->loginField($role) => 'The provided credentials do not match our records.',
            ]);
        }

        $request->session()->regenerate();

        Auth::shouldUse($guard);

        /** @var User|Admin $user */
        $user = Auth::guard($guard)->user();

        if ($user->status !== 'active') {
            Auth::guard($guard)->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                $this->loginField($role) => 'This account is not active. Contact the SIWES office for support.',
            ]);
        }

        if (! $this->userCanUsePortal($user, $role)) {
            $auditLogger->record('auth.portal_denied', $user, $request, metadata: ['portal' => $role]);
            Auth::guard($guard)->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            abort(403);
        }

        $user->forceFill(['last_login_at' => now()])->save();
        $auditLogger->record('auth.login_success', $user, $request, metadata: ['portal' => $role]);

        $request->session()->put('otp.verified', true);

        return AjaxResponse::success($request, 'Signed in successfully.', RoleRedirector::dashboardFor($user));
    }

    public function destroy(Request $request, AuditLogger $auditLogger): JsonResponse|RedirectResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user) {
            $auditLogger->record('auth.logout', $user, $request);
        }

        Auth::guard('web')->logout();
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return AjaxResponse::success($request, 'Signed out successfully.', route('home'));
        }

        return redirect()
            ->route('home')
            ->with('status', 'Signed out successfully.')
            ->with('toast_title', 'Logout successful')
            ->with('toast_tone', 'success');
    }

    private function userCanUsePortal(User|Admin $user, string $role): bool
    {
        return match ($role) {
            'admin' => $user instanceof Admin,
            'supervisor' => $user instanceof User && $user->supervisor()->exists(),
            'student' => $user instanceof User && $user->student()->exists(),
            default => false,
        };
    }

    /**
     * @return array{email: string, password: string}
     */
    private function credentials(Request $request, string $role): array
    {
        if ($role !== 'student') {
            return $request->validate([
                'email' => ['required', 'email'],
                'password' => ['required', 'string'],
            ]);
        }

        $validated = $request->validate([
            'matric_no' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $student = Student::query()
            ->with('user')
            ->where('matric_no', trim((string) $validated['matric_no']))
            ->first();

        return [
            'email' => $student?->user?->email ?? '',
            'password' => $validated['password'],
        ];
    }

    private function loginField(string $role): string
    {
        return $role === 'student' ? 'matric_no' : 'email';
    }
}
