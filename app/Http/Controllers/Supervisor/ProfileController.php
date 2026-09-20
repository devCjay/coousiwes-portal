<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\Supervisor;
use App\Services\AuditLogger;
use App\Support\AjaxResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View|RedirectResponse
    {
        $supervisor = $request->user()?->supervisor;
        abort_unless($supervisor instanceof Supervisor, 403);

        if ($this->hasBankDetails($supervisor)) {
            return redirect()->route('supervisor.dashboard');
        }

        return view('pages.supervisor.profile-setup', [
            'supervisor' => $supervisor->load('user'),
            'metadata' => $supervisor->metadata ?? [],
            'banks' => config('siwes_profile.banks', []),
        ]);
    }

    public function update(Request $request, AuditLogger $auditLogger): JsonResponse|RedirectResponse
    {
        $supervisor = $request->user()?->supervisor;
        abort_unless($supervisor instanceof Supervisor, 403);

        $validated = $request->validate([
            'bank_name' => ['required', 'string', 'max:160'],
            'account_number' => ['required', 'digits_between:10,12'],
            'account_name' => ['required', 'string', 'max:160'],
            'sort_code' => ['required', 'string', 'max:20'],
        ]);

        $metadata = array_merge($supervisor->metadata ?? [], [
            'bank_name' => $validated['bank_name'],
            'account_number' => $validated['account_number'],
            'account_name' => $validated['account_name'],
            'sort_code' => $validated['sort_code'],
            'bank_completed_at' => now()->toDateTimeString(),
        ]);

        $supervisor->update(['metadata' => $metadata]);

        $auditLogger->record('supervisors.profile_updated', $request->user(), $request, $supervisor, [
            'fields' => ['bank_name', 'account_number', 'account_name', 'sort_code'],
        ]);

        return AjaxResponse::success(
            $request,
            'Supervisor bank details saved.',
            route('supervisor.dashboard', absolute: false),
            reload: false,
        );
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
