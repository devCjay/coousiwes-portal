<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicLevel;
use App\Models\AcademicSession;
use App\Models\Assessment;
use App\Models\AssessmentRubricItem;
use App\Models\Department;
use App\Models\Student;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class AssessmentController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->can('assessments.view'), 403);

        return view('pages.admin.assessments', [
            'activeRubricCount' => AssessmentRubricItem::query()->where('is_active', true)->count(),
            'submittedAssessmentCount' => Assessment::query()->count(),
            'rubricItems' => AssessmentRubricItem::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
            'departments' => Department::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'sessions' => AcademicSession::query()->orderByDesc('starts_on')->get(['id', 'name']),
            'levels' => AcademicLevel::query()->where('is_active', true)->orderBy('level')->get(['id', 'name', 'level']),
        ]);
    }

    public function logbookScoreSheet(Request $request): Response
    {
        abort_unless($request->user()?->can('assessments.export'), 403);

        $filters = $request->validate([
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')->whereNull('deleted_at')],
            'academic_session_id' => ['nullable', 'integer', Rule::exists('academic_sessions', 'id')->whereNull('deleted_at')],
            'academic_level_id' => ['nullable', 'integer', Rule::exists('academic_levels', 'id')->whereNull('deleted_at')],
        ]);

        $assessments = Assessment::query()
            ->with(['student.user', 'student.department', 'student.academicSession', 'student.academicLevel', 'supervisor.user'])
            ->whereHas('student', function ($query) use ($filters): void {
                $query
                    ->when($filters['department_id'] ?? null, fn ($studentQuery, int $departmentId) => $studentQuery->where('department_id', $departmentId))
                    ->when($filters['academic_session_id'] ?? null, fn ($studentQuery, int $sessionId) => $studentQuery->where('academic_session_id', $sessionId))
                    ->when($filters['academic_level_id'] ?? null, fn ($studentQuery, int $levelId) => $studentQuery->where('academic_level_id', $levelId));
            })
            ->orderBy(
                Student::query()
                    ->select('department_id')
                    ->whereColumn('students.id', 'assessments.student_id')
                    ->limit(1),
            )
            ->orderBy(
                Student::query()
                    ->select('matric_no')
                    ->whereColumn('students.id', 'assessments.student_id')
                    ->limit(1),
            )
            ->get();

        return response(view('exports.logbook-score-sheet', [
            'assessments' => $assessments,
        ])->render(), 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="LOG BOOK SCORE SHEET.xls"',
        ]);
    }
}
