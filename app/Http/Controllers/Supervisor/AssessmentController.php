<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supervisor\StoreAssessmentRequest;
use App\Models\Assessment;
use App\Models\AssessmentRubricItem;
use App\Models\Student;
use App\Notifications\PortalNotification;
use App\Services\AssessmentService;
use App\Services\AuditLogger;
use App\Support\AjaxResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssessmentController extends Controller
{
    public function index(Request $request): View
    {
        $supervisor = $request->user()?->supervisor;
        abort_unless($supervisor !== null, 403);

        return view('pages.supervisor.assessments', [
            'supervisor' => $supervisor,
            'assignments' => $supervisor->activeAssignments()
                ->with(['student.user', 'student.department', 'student.academicLevel', 'assessment'])
                ->latest('assigned_at')
                ->get(),
            'rubricItems' => AssessmentRubricItem::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'assessments' => Assessment::query()
                ->where('supervisor_id', $supervisor->id)
                ->with(['student.user', 'scores.rubricItem'])
                ->latest('submitted_at')
                ->get(),
        ]);
    }

    public function store(StoreAssessmentRequest $request, AssessmentService $assessmentService, AuditLogger $auditLogger): JsonResponse|RedirectResponse
    {
        $supervisor = $request->user()?->supervisor;
        abort_unless($supervisor !== null, 403);

        $student = Student::query()->with('user')->findOrFail((int) $request->validated('student_id'));

        try {
            $assessment = $assessmentService->submit(
                $supervisor,
                $student,
                $request->validated('scores'),
                (string) $request->validated('feedback'),
            );
        } catch (\RuntimeException $exception) {
            return AjaxResponse::error($request, $exception->getMessage(), key: 'assessment');
        }

        $auditLogger->record('assessments.submitted', $request->user(), $request, $assessment, [
            'student_id' => $student->id,
            'score' => $assessment->total_score,
            'max_score' => $assessment->max_score,
        ]);

        $student->user->notify(new PortalNotification([
            'title' => 'Supervisor feedback submitted',
            'message' => 'Your SIWES supervisor assessment is now available.',
            'tone' => 'success',
            'action_url' => route('student.feedback.index'),
            'meta' => ['assessment_id' => $assessment->id],
        ]));

        return AjaxResponse::success($request, 'Assessment submitted.', reload: true);
    }
}
