<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\UpdateProfileStepRequest;
use App\Http\Requests\Student\UpdateProfileRequest;
use App\Models\AcademicSession;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Student;
use App\Services\AuditLogger;
use App\Support\AjaxResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(): View|RedirectResponse
    {
        $student = request()->user()?->student;
        abort_unless($student instanceof Student, 403);

        if (! $student->hasCompleteProfile()) {
            return redirect()->route('student.profile.edit');
        }

        return view('pages.student.profile-show', [
            'student' => $student->load(['user', 'faculty', 'department', 'academicSession', 'placement.academicLevel']),
            'nationalities' => config('siwes_profile.nationalities', []),
            'states' => config('siwes_profile.states', []),
            'banks' => config('siwes_profile.banks', []),
            'faculties' => Faculty::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']),
            'departments' => Department::query()->where('is_active', true)->orderBy('name')->get(['id', 'faculty_id', 'name', 'code']),
            'sessions' => AcademicSession::query()->orderByDesc('starts_on')->get(['id', 'name', 'starts_on', 'ends_on', 'is_active']),
            'activeSession' => AcademicSession::active(),
        ]);
    }

    public function edit(): View|RedirectResponse
    {
        $student = request()->user()?->student;
        abort_unless($student instanceof Student, 403);

        if ($student->hasCompleteProfile()) {
            return redirect()->route('student.profile.show');
        }

        return view('pages.student.profile-setup', [
            'student' => $student->load(['user', 'faculty', 'department', 'academicLevel', 'academicSession']),
            'nationalities' => config('siwes_profile.nationalities', []),
            'states' => config('siwes_profile.states', []),
            'banks' => config('siwes_profile.banks', []),
            'faculties' => Faculty::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']),
            'departments' => Department::query()->where('is_active', true)->orderBy('name')->get(['id', 'faculty_id', 'name', 'code']),
            'sessions' => AcademicSession::query()->orderByDesc('starts_on')->get(['id', 'name', 'starts_on', 'ends_on', 'is_active']),
            'activeSession' => AcademicSession::active(),
        ]);
    }

    public function update(UpdateProfileRequest $request, AuditLogger $auditLogger): JsonResponse|RedirectResponse
    {
        $student = $request->user()?->student;
        abort_unless($student instanceof Student, 403);

        $validated = $request->validated();
        $request->user()->update([
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
        ]);
        $student->update([
            'gender' => $validated['gender'] ?? null,
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'address' => $validated['address'] ?? null,
        ]);

        $auditLogger->record('students.profile_updated', $request->user(), $request, $student);

        return AjaxResponse::success($request, 'Profile updated.');
    }

    public function updateStep(UpdateProfileStepRequest $request, AuditLogger $auditLogger): JsonResponse|RedirectResponse
    {
        $student = $request->user()?->student;
        abort_unless($student instanceof Student, 403);

        $validated = $request->validated();
        $metadata = $student->metadata ?? [];

        match ($validated['step']) {
            'basic' => $this->saveBasicStep($request, $student, $validated, $metadata),
            'contact' => $this->saveContactStep($student, $validated, $metadata),
            'academic' => $this->saveAcademicStep($student, $validated),
            'bank' => $this->saveBankStep($student, $validated, $metadata),
        };

        $student->refresh()->load('user');
        $completion = $student->profileCompletionPercent();
        $shouldRedirectToMilestone = $completion >= 100 && $request->input('source') !== 'profile';

        $auditLogger->record('students.profile_step_updated', $request->user(), $request, $student, [
            'step' => $validated['step'],
            'completion' => $completion,
        ]);

        return AjaxResponse::success(
            $request,
            $completion >= 100 ? 'Profile completed. Welcome to your dashboard.' : 'Step saved successfully.',
            $shouldRedirectToMilestone ? route('student.profile.complete', absolute: false) : null,
            reload: false,
            data: [
                'step' => $validated['step'],
                'completion' => $completion,
            ],
        );
    }

    public function complete(): View|RedirectResponse
    {
        $student = request()->user()?->student;
        abort_unless($student instanceof Student, 403);

        if (! $student->hasCompleteProfile()) {
            return redirect()->route('student.profile.edit');
        }

        return view('pages.student.profile-complete', [
            'student' => $student->load('user'),
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @param  array<string, mixed>  $metadata
     */
    private function saveBasicStep(UpdateProfileStepRequest $request, Student $student, array $validated, array $metadata): void
    {
        $request->user()->update([
            'email' => $validated['email'],
            'phone' => $validated['phone'],
        ]);

        $metadata['nationality'] = $validated['nationality'];
        $student->update([
            'gender' => $validated['gender'],
            'date_of_birth' => $validated['date_of_birth'],
            'metadata' => $metadata,
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @param  array<string, mixed>  $metadata
     */
    private function saveContactStep(Student $student, array $validated, array $metadata): void
    {
        $metadata['state'] = $validated['state'];
        $metadata['lga'] = $validated['lga'];
        $student->update([
            'address' => $validated['address'],
            'metadata' => $metadata,
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function saveAcademicStep(Student $student, array $validated): void
    {
        $student->update([
            'faculty_id' => $validated['faculty_id'],
            'department_id' => $validated['department_id'],
            'academic_session_id' => $validated['academic_session_id'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @param  array<string, mixed>  $metadata
     */
    private function saveBankStep(Student $student, array $validated, array $metadata): void
    {
        $metadata['bank_name'] = $validated['bank_name'];
        $metadata['account_number'] = $validated['account_number'];
        $metadata['sort_code'] = $validated['sort_code'];

        $student->update(['metadata' => $metadata]);
    }
}
