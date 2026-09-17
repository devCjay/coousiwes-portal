<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\MailConfiguration;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;
use Throwable;

class PasswordResetController extends Controller
{
    public function create(Request $request): View
    {
        return view('pages.auth.forgot-password', [
            'role' => ucfirst((string) $request->query('role', 'student')),
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        try {
            MailConfiguration::apply();
            $status = Password::broker('users')->sendResetLink($validated);
        } catch (Throwable $exception) {
            report($exception);

            $message = 'Reset email could not be sent. Please confirm the email settings and try again.';

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['message' => $message], 500);
            }

            return back()
                ->withInput($request->only('email'))
                ->with('toast_title', 'Reset failed')
                ->with('toast_tone', 'danger')
                ->withErrors(['email' => $message]);
        }

        $message = __($status);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => $message,
                'reload' => false,
            ], $status === Password::RESET_LINK_SENT ? 200 : 422);
        }

        return back()
            ->with('toast_title', $status === Password::RESET_LINK_SENT ? 'Reset link sent' : 'Reset failed')
            ->with('toast_tone', $status === Password::RESET_LINK_SENT ? 'success' : 'danger')
            ->with('status', $message);
    }

    public function edit(Request $request, string $token): View
    {
        return view('pages.auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function update(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::default()],
        ]);

        $resetUser = null;

        $status = Password::broker('users')->reset(
            $validated,
            function (User $user, string $password) use (&$resetUser): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                $resetUser = $user;

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'message' => __($status),
                    'errors' => ['email' => [__($status)]],
                ], 422);
            }

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => __($status)]);
        }

        $loginRoute = $resetUser?->supervisor()->exists() ? 'login.supervisor' : 'login.student';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => 'Your password has been reset. Sign in with your new password.',
                'redirect' => route($loginRoute),
                'reload' => false,
            ]);
        }

        return redirect()
            ->route($loginRoute)
            ->with('toast_title', 'Password reset')
            ->with('toast_tone', 'success')
            ->with('status', 'Your password has been reset. Sign in with your new password.');
    }
}
