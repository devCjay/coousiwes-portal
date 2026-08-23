<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAcademicLevelRequest;
use App\Http\Requests\Admin\StoreAcademicSessionRequest;
use App\Http\Requests\Admin\StoreDepartmentRequest;
use App\Http\Requests\Admin\StoreFacultyRequest;
use App\Models\AcademicLevel;
use App\Models\AcademicSession;
use App\Models\Department;
use App\Models\Faculty;
use App\Services\AuditLogger;
use App\Support\AjaxResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AcademicStructureController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function index(): View
    {
        return view('pages.admin.academics', [
            'faculties' => Faculty::query()->withCount('departments')->latest()->get(),
            'departments' => Department::query()->with('faculty')->withCount('students')->latest()->get(),
            'levels' => AcademicLevel::query()->orderBy('level')->get(),
            'sessions' => AcademicSession::query()->latest('starts_on')->get(),
            'activeSession' => AcademicSession::active(),
        ]);
    }

    public function storeFaculty(StoreFacultyRequest $request): JsonResponse|RedirectResponse
    {
        $faculty = Faculty::query()->create($request->validated());

        $this->auditLogger->record('academics.faculty_created', $request->user(), $request, $faculty, $faculty->only(['name', 'code']));

        return AjaxResponse::success($request, 'Faculty created.');
    }

    public function updateFaculty(StoreFacultyRequest $request, Faculty $faculty): JsonResponse|RedirectResponse
    {
        $before = $faculty->only(['name', 'code', 'is_active']);
        $faculty->update($request->validated());

        $this->auditLogger->record('academics.faculty_updated', $request->user(), $request, $faculty, [
            'before' => $before,
            'after' => $faculty->only(['name', 'code', 'is_active']),
        ]);

        return AjaxResponse::success($request, 'Faculty updated.');
    }

    public function destroyFaculty(Request $request, Faculty $faculty): JsonResponse|RedirectResponse
    {
        abort_unless($request->user()?->can('academics.manage'), 403);

        if ($faculty->departments()->exists()) {
            return AjaxResponse::error($request, 'Faculty cannot be deleted while departments are attached.', key: 'faculty');
        }

        $faculty->delete();
        $this->auditLogger->record('academics.faculty_deleted', $request->user(), $request, $faculty, $faculty->only(['name', 'code']));

        return AjaxResponse::success($request, 'Faculty archived.');
    }

    public function restoreFaculty(Request $request, int $faculty): JsonResponse|RedirectResponse
    {
        abort_unless($request->user()?->can('academics.manage'), 403);

        $record = Faculty::withTrashed()->findOrFail($faculty);
        $record->restore();

        $this->auditLogger->record('academics.faculty_restored', $request->user(), $request, $record, $record->only(['name', 'code']));

        return AjaxResponse::success($request, 'Faculty restored.');
    }

    public function storeDepartment(StoreDepartmentRequest $request): JsonResponse|RedirectResponse
    {
        $department = Department::query()->create($request->validated());

        $this->auditLogger->record('academics.department_created', $request->user(), $request, $department, $department->only(['name', 'code', 'faculty_id']));

        return AjaxResponse::success($request, 'Department created.');
    }

    public function updateDepartment(StoreDepartmentRequest $request, Department $department): JsonResponse|RedirectResponse
    {
        $before = $department->only(['faculty_id', 'name', 'code', 'is_active']);
        $department->update($request->validated());

        $this->auditLogger->record('academics.department_updated', $request->user(), $request, $department, [
            'before' => $before,
            'after' => $department->only(['faculty_id', 'name', 'code', 'is_active']),
        ]);

        return AjaxResponse::success($request, 'Department updated.');
    }

    public function destroyDepartment(Request $request, Department $department): JsonResponse|RedirectResponse
    {
        abort_unless($request->user()?->can('academics.manage'), 403);

        if ($department->students()->exists()) {
            return AjaxResponse::error($request, 'Department cannot be deleted while students are attached.', key: 'department');
        }

        $department->delete();
        $this->auditLogger->record('academics.department_deleted', $request->user(), $request, $department, $department->only(['name', 'code', 'faculty_id']));

        return AjaxResponse::success($request, 'Department archived.');
    }

    public function storeLevel(StoreAcademicLevelRequest $request): JsonResponse|RedirectResponse
    {
        $level = AcademicLevel::query()->create($request->validated());

        $this->auditLogger->record('academics.level_created', $request->user(), $request, $level, $level->only(['name', 'level']));

        return AjaxResponse::success($request, 'Level created.');
    }

    public function updateLevel(StoreAcademicLevelRequest $request, AcademicLevel $academicLevel): JsonResponse|RedirectResponse
    {
        $before = $academicLevel->only(['name', 'level', 'is_active']);
        $academicLevel->update($request->validated());

        $this->auditLogger->record('academics.level_updated', $request->user(), $request, $academicLevel, [
            'before' => $before,
            'after' => $academicLevel->only(['name', 'level', 'is_active']),
        ]);

        return AjaxResponse::success($request, 'Level updated.');
    }

    public function storeSession(StoreAcademicSessionRequest $request): JsonResponse|RedirectResponse
    {
        $session = AcademicSession::query()->create($request->validated());

        if ($request->boolean('is_active')) {
            $session->activate();
        }

        $this->auditLogger->record('academics.session_created', $request->user(), $request, $session, $session->only(['name', 'starts_on', 'ends_on', 'is_active']));

        return AjaxResponse::success($request, 'Academic session created.');
    }

    public function updateSession(StoreAcademicSessionRequest $request, AcademicSession $academicSession): JsonResponse|RedirectResponse
    {
        $before = $academicSession->only(['name', 'starts_on', 'ends_on', 'is_active']);
        $academicSession->update($request->validated());

        if ($request->boolean('is_active')) {
            $academicSession->activate();
        }

        $this->auditLogger->record('academics.session_updated', $request->user(), $request, $academicSession, [
            'before' => $before,
            'after' => $academicSession->only(['name', 'starts_on', 'ends_on', 'is_active']),
        ]);

        return AjaxResponse::success($request, 'Academic session updated.');
    }

    public function activateSession(Request $request, AcademicSession $academicSession): JsonResponse|RedirectResponse
    {
        abort_unless($request->user()?->can('academics.manage'), 403);

        $academicSession->activate();

        $this->auditLogger->record('academics.session_activated', $request->user(), $request, $academicSession, $academicSession->only(['name']));

        return AjaxResponse::success($request, 'Active academic session changed.');
    }
}
