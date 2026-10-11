<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Models\AcademicLevel;
use App\Models\AcademicSession;
use App\Models\Assessment;
use App\Models\SiwesCoordinator;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LogbookGradeSheetController extends Controller
{
    public function index(Request $request): View
    {
        $coordinator = $request->user()?->siwesCoordinator;
        abort_unless($coordinator instanceof SiwesCoordinator, 403);

        return view('pages.coordinator.logbook-grade-sheet', [
            'coordinator' => $coordinator->load(['department', 'faculty']),
            'levels' => AcademicLevel::query()->where('is_active', true)->orderBy('level')->get(['id', 'name', 'level']),
            'sessions' => AcademicSession::query()->orderByDesc('starts_on')->get(['id', 'name']),
            'assessmentCount' => Assessment::query()
                ->whereHas('student', fn ($query) => $query->where('department_id', $coordinator->department_id))
                ->count(),
        ]);
    }

    public function export(Request $request): Response
    {
        $coordinator = $request->user()?->siwesCoordinator;
        abort_unless($coordinator instanceof SiwesCoordinator, 403);

        $filters = $request->validate([
            'academic_session_id' => ['nullable', 'integer', Rule::exists('academic_sessions', 'id')->whereNull('deleted_at')],
            'academic_level_id' => ['nullable', 'integer', Rule::exists('academic_levels', 'id')->whereNull('deleted_at')],
        ]);

        $departmentId = $coordinator->department_id;
        $assessments = Assessment::query()
            ->with(['student.user', 'student.department', 'student.academicSession', 'student.academicLevel', 'supervisor.user'])
            ->whereHas('student', function ($query) use ($filters, $departmentId): void {
                $query
                    ->where('department_id', $departmentId)
                    ->when($filters['academic_session_id'] ?? null, fn ($studentQuery, int $sessionId) => $studentQuery->where('academic_session_id', $sessionId))
                    ->when($filters['academic_level_id'] ?? null, fn ($studentQuery, int $levelId) => $studentQuery->where('academic_level_id', $levelId));
            })
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
            'Content-Disposition' => 'attachment; filename="COORDINATOR LOG BOOK SCORE SHEET.xls"',
        ]);
    }
}
