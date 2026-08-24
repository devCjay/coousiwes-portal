<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicLevel;
use App\Models\AcademicSession;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Student;
use App\Models\StudentPlacement;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class GenerateListController extends Controller
{
    public function index(): View
    {
        return view('pages.admin.generate-list', [
            'studentCount' => Student::query()->count(),
            'facultyCount' => Faculty::query()->count(),
            'departmentCount' => Department::query()->count(),
            'faculties' => Faculty::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'departments' => Department::query()->where('is_active', true)->orderBy('name')->get(['id', 'faculty_id', 'name']),
            'sessions' => AcademicSession::query()->orderByDesc('starts_on')->get(['id', 'name']),
            'levels' => AcademicLevel::query()->where('is_active', true)->orderBy('level')->get(['id', 'name', 'level']),
        ]);
    }

    public function master(Request $request): Response
    {
        abort_unless($request->user()?->can('generate-list.export'), 403);
        $filters = $this->validatedFilters($request);

        $students = Student::query()
            ->with(['user', 'faculty', 'department', 'academicLevel', 'academicSession', 'placement.academicLevel', 'placement.academicSession'])
            ->when($filters['faculty_id'] ?? null, fn ($query, int $facultyId) => $query->where('faculty_id', $facultyId))
            ->when($filters['department_id'] ?? null, fn ($query, int $departmentId) => $query->where('department_id', $departmentId))
            ->when($filters['academic_session_id'] ?? null, fn ($query, int $sessionId) => $query->where('academic_session_id', $sessionId))
            ->when($filters['academic_level_id'] ?? null, fn ($query, int $levelId) => $query->where('academic_level_id', $levelId))
            ->orderBy(
                Faculty::query()
                    ->select('name')
                    ->whereColumn('faculties.id', 'students.faculty_id')
                    ->limit(1),
            )
            ->orderBy(
                Department::query()
                    ->select('name')
                    ->whereColumn('departments.id', 'students.department_id')
                    ->limit(1),
            )
            ->orderBy('matric_no')
            ->get();

        $year = (int) ($students->first(fn (Student $student): bool => $student->placement !== null)?->placement?->siwes_year ?? now()->year);
        $session = $this->sessionLabel($students->first()?->placement?->academicSession ?? $students->first()?->academicSession, $year);

        return $this->xlsResponse('exports.master-list', [
            'students' => $students,
            'year' => $year,
            'session' => $session,
        ], "MASTER LIST {$year}.xls");
    }

    public function placement(Request $request): Response
    {
        abort_unless($request->user()?->can('generate-list.export'), 403);
        $filters = $this->validatedFilters($request);

        $placements = StudentPlacement::query()
            ->with(['student.user', 'student.faculty', 'student.department', 'student.academicLevel', 'student.academicSession', 'academicLevel', 'academicSession'])
            ->whereHas('student')
            ->when($filters['faculty_id'] ?? null, fn ($query, int $facultyId) => $query->whereHas('student', fn ($studentQuery) => $studentQuery->where('faculty_id', $facultyId)))
            ->when($filters['department_id'] ?? null, fn ($query, int $departmentId) => $query->whereHas('student', fn ($studentQuery) => $studentQuery->where('department_id', $departmentId)))
            ->when($filters['academic_session_id'] ?? null, fn ($query, int $sessionId) => $query->where('academic_session_id', $sessionId))
            ->when($filters['academic_level_id'] ?? null, fn ($query, int $levelId) => $query->where('academic_level_id', $levelId))
            ->orderBy(
                Student::query()
                    ->select('faculty_id')
                    ->whereColumn('students.id', 'student_placements.student_id')
                    ->limit(1),
            )
            ->orderBy(
                Student::query()
                    ->select('department_id')
                    ->whereColumn('students.id', 'student_placements.student_id')
                    ->limit(1),
            )
            ->orderBy(
                Student::query()
                    ->select('matric_no')
                    ->whereColumn('students.id', 'student_placements.student_id')
                    ->limit(1),
            )
            ->get();

        $year = (int) ($placements->first()?->siwes_year ?? now()->year);
        $session = $this->sessionLabel($placements->first()?->academicSession, $year);

        return $this->xlsResponse('exports.placement-list', [
            'placements' => $placements,
            'year' => $year,
            'session' => $session,
        ], "PLACEMENT LIST {$year}.xls");
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function xlsResponse(string $view, array $data, string $filename): Response
    {
        return response(view($view, $data)->render(), 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    private function sessionLabel(?AcademicSession $session, int $year): string
    {
        return $session?->name ?? ($year - 1).'/'.$year;
    }

    /**
     * @return array{faculty_id?: int, department_id?: int, academic_session_id?: int, academic_level_id?: int}
     */
    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'faculty_id' => ['nullable', 'integer', Rule::exists('faculties', 'id')->whereNull('deleted_at')],
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')->whereNull('deleted_at')],
            'academic_session_id' => ['nullable', 'integer', Rule::exists('academic_sessions', 'id')->whereNull('deleted_at')],
            'academic_level_id' => ['nullable', 'integer', Rule::exists('academic_levels', 'id')->whereNull('deleted_at')],
        ]);
    }
}
