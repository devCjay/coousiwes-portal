<?php

namespace App\Http\Controllers;

use App\Services\AuditLogger;
use App\Support\AjaxResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AccountPasswordController extends Controller
{
    public function edit(Request $request): View
    {
        return view('pages.account.password', [
            'user' => $request->user(),
            'role' => $this->roleLabel($request),
            'dashboard' => $this->dashboardRoute($request),
        ]);
    }

    public function update(Request $request, AuditLogger $auditLogger): JsonResponse|RedirectResponse
    {
        if (! Hash::check((string) $request->input('current_password'), $request->user()->password)) {
            return AjaxResponse::error($request, 'The provided password does not match your current password.', 422, 'current_password');
        }

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', Password::default(), 'confirmed'],
        ]);

        $request->user()->forceFill([
            'password' => Hash::make((string) $validated['password']),
        ])->save();

        $auditLogger->record('account.password_updated', $request->user(), $request, $request->user());

        return AjaxResponse::success($request, 'Password changed successfully.');
    }

    private function roleLabel(Request $request): string
    {
        return match (true) {
            $request->user()?->hasRole('supervisor') => 'Supervisor',
            $request->user()?->hasRole('student') => 'Student',
            default => 'Admin',
        };
    }

    private function dashboardRoute(Request $request): string
    {
        return match (true) {
            $request->user()?->hasRole('supervisor') => route('supervisor.dashboard'),
            $request->user()?->hasRole('student') => route('student.dashboard'),
            default => route('admin.dashboard'),
        };
    }
}
