<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignSupervisorRequest;
use App\Http\Requests\Admin\BulkAssignSupervisorRequest;
use App\Models\Student;
use App\Models\Supervisor;
use App\Models\SupervisorStudentAssignment;
use App\Services\AuditLogger;
use App\Services\SupervisorAssignmentService;
use App\Support\AjaxResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SupervisorAssignmentController extends Controller
{
    public function __construct(
        private readonly SupervisorAssignmentService $assignmentService,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function store(AssignSupervisorRequest $request): JsonResponse|RedirectResponse
    {
        try {
            $assignment = $this->assignmentService->assign(
                Supervisor::query()->findOrFail($request->integer('supervisor_id')),
                Student::query()->findOrFail($request->integer('student_id')),
                $request->user(),
            );
        } catch (\RuntimeException $exception) {
            return AjaxResponse::error($request, $exception->getMessage(), key: 'assignment');
        }

        $this->auditLogger->record('supervisors.assigned', $request->user(), $request, $assignment, $assignment->only(['supervisor_id', 'student_id']));

        return AjaxResponse::success($request, 'Student assigned to supervisor.');
    }

    public function bulk(BulkAssignSupervisorRequest $request): JsonResponse|RedirectResponse
    {
        $result = $this->assignmentService->bulkAssign(
            Supervisor::query()->findOrFail($request->integer('supervisor_id')),
            $request->validated(),
            $request->user(),
        );

        $this->auditLogger->record('supervisors.bulk_assigned', $request->user(), $request, metadata: $result);

        return AjaxResponse::success($request, "{$result['assigned']} assigned, {$result['skipped']} skipped.");
    }

    public function revoke(Request $request, SupervisorStudentAssignment $assignment): JsonResponse|RedirectResponse
    {
        abort_unless($request->user()?->can('supervisors.assign'), 403);

        $assignment = $this->assignmentService->revoke($assignment, $request->user(), $request->string('reason')->toString() ?: null);
        $this->auditLogger->record('supervisors.assignment_revoked', $request->user(), $request, $assignment, $assignment->only(['supervisor_id', 'student_id']));

        return AjaxResponse::success($request, 'Supervisor assignment revoked.');
    }

    public function bulkRevoke(Request $request): JsonResponse|RedirectResponse
    {
        abort_unless($request->user()?->can('supervisors.assign'), 403);

        $validated = $request->validate([
            'assignment_ids' => ['required', 'array', 'min:1'],
            'assignment_ids.*' => ['integer', 'exists:supervisor_student_assignments,id'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $count = 0;
        $assignments = SupervisorStudentAssignment::query()
            ->whereIn('id', $validated['assignment_ids'])
            ->whereNull('revoked_at')
            ->get();

        foreach ($assignments as $assignment) {
            $this->assignmentService->revoke($assignment, $request->user(), $request->string('reason')->toString() ?: null);
            $this->auditLogger->record('supervisors.assignment_revoked', $request->user(), $request, $assignment, $assignment->only(['supervisor_id', 'student_id']));
            $count++;
        }

        return AjaxResponse::success($request, "{$count} assignment(s) revoked.");
    }

    public function export(Request $request): Response
    {
        abort_unless($request->user()?->can('supervisors.view'), 403);

        $handle = fopen('php://temp', 'w+');
        fputcsv($handle, ['Supervisor', 'Student', 'Matric No', 'Department', 'Assigned At', 'Revoked At']);

        SupervisorStudentAssignment::query()->with(['supervisor.user', 'student.user', 'student.department'])->latest('assigned_at')->each(function (SupervisorStudentAssignment $assignment) use ($handle): void {
            fputcsv($handle, [
                $assignment->supervisor->user->name,
                $assignment->student->user->name,
                $assignment->student->matric_no,
                $assignment->student->department->name,
                $assignment->assigned_at->toDateTimeString(),
                $assignment->revoked_at?->toDateTimeString(),
            ]);
        });

        rewind($handle);

        return response((string) stream_get_contents($handle), 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=supervisor-assignments.csv',
        ]);
    }
}
