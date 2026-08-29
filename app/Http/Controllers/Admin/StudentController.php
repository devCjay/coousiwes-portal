<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreStudentRequest;
use App\Http\Requests\Admin\UpdateStudentRequest;
use App\Models\AcademicLevel;
use App\Models\AcademicSession;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Student;
use App\Models\StudentImport;
use App\Models\StudentPlacement;
use App\Services\AuditLogger;
use App\Services\StudentImportService;
use App\Services\StudentManager;
use App\Support\AjaxResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use RuntimeException;

class StudentController extends Controller
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly StudentManager $studentManager,
        private readonly StudentImportService $studentImportService,
    ) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()?->can('students.view'), 403);

        $students = Student::query()
            ->with(['user', 'faculty', 'department', 'academicLevel', 'academicSession', 'placement.academicLevel', 'placement.academicSession'])
            ->when($request->filled('status'), function ($query) use ($request): void {
                match ($request->string('status')->toString()) {
                    'active' => $query->where('activation_status', Student::STATUS_ACTIVE),
                    'inactive' => $query->where('activation_status', Student::STATUS_INACTIVE),
                    'suspended' => $query->where('activation_status', Student::STATUS_SUSPENDED),
                    default => null,
                };
            })
            ->when($request->filled('academic_session_id'), fn ($query) => $query->where('academic_session_id', $request->integer('academic_session_id')))
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->toString();
                $query->where(function ($inner) use ($search): void {
                    $inner->where('matric_no', 'like', "%{$search}%")
                        ->orWhereHas('department', fn ($departmentQuery) => $departmentQuery->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('user', fn ($userQuery) => $userQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('pages.admin.students', [
            'students' => $students,
            'faculties' => Faculty::query()->where('is_active', true)->orderBy('name')->get(),
            'departments' => Department::query()->where('is_active', true)->with('faculty')->orderBy('name')->get(),
            'levels' => AcademicLevel::query()->where('is_active', true)->orderBy('level')->get(),
            'sessions' => AcademicSession::query()->orderByDesc('starts_on')->get(),
            'imports' => StudentImport::query()->with('uploader')->latest()->limit(10)->get(),
            'postingLevels' => AcademicLevel::query()
                ->where('is_active', true)
                ->whereBetween('level', [200, 600])
                ->orderBy('level')
                ->get(['id', 'name', 'level']),
            'postingStates' => collect(config('siwes_profile.states', []))
                ->pluck('name')
                ->sort()
                ->values(),
        ]);
    }

    public function store(StoreStudentRequest $request): JsonResponse|RedirectResponse
    {
        try {
            $student = $this->studentManager->create($request->validated());
        } catch (RuntimeException $exception) {
            return AjaxResponse::error($request, $exception->getMessage(), key: 'student');
        }

        $this->auditLogger->record('students.created', $request->user(), $request, $student, $student->only(['matric_no']));

        return AjaxResponse::success($request, 'Student created.');
    }

    public function update(UpdateStudentRequest $request, Student $student): JsonResponse|RedirectResponse
    {
        $before = $student->only(['matric_no', 'activation_status']);
        $student = $this->studentManager->update($student, $request->validated());

        $this->auditLogger->record('students.updated', $request->user(), $request, $student, [
            'before' => $before,
            'after' => $student->only(['matric_no', 'activation_status']),
        ]);

        return AjaxResponse::success($request, 'Student updated.');
    }

    public function show(Request $request, Student $student): View
    {
        abort_unless($request->user()?->can('students.view'), 403);

        return view('pages.admin.student-show', [
            'student' => $student->load([
                'user',
                'faculty',
                'department',
                'academicLevel',
                'academicSession',
                'placement.academicLevel',
                'placement.academicSession',
                'placement.ticket',
                'payments',
                'activeSupervisorAssignment.supervisor.user',
            ]),
            'faculties' => Faculty::query()->where('is_active', true)->orderBy('name')->get(),
            'departments' => Department::query()->where('is_active', true)->with('faculty')->orderBy('name')->get(),
            'levels' => AcademicLevel::query()->where('is_active', true)->orderBy('level')->get(),
            'sessions' => AcademicSession::query()->orderByDesc('starts_on')->get(),
        ]);
    }

    public function suspend(Request $request, Student $student): JsonResponse|RedirectResponse
    {
        abort_unless($request->user()?->can('students.suspend'), 403);

        $student = $this->studentManager->changeStatus($student, Student::STATUS_SUSPENDED);
        $this->auditLogger->record('students.suspended', $request->user(), $request, $student);

        return AjaxResponse::success($request, 'Student suspended.');
    }

    public function reactivate(Request $request, Student $student): JsonResponse|RedirectResponse
    {
        abort_unless($request->user()?->can('students.update'), 403);

        $student = $this->studentManager->changeStatus($student, Student::STATUS_ACTIVE);
        $this->auditLogger->record('students.reactivated', $request->user(), $request, $student);

        return AjaxResponse::success($request, 'Student reactivated.');
    }

    public function resetPassword(Request $request, Student $student): JsonResponse|RedirectResponse
    {
        abort_unless($request->user()?->can('students.update'), 403);

        $student->user->forceFill([
            'password' => Hash::make($student->matric_no),
        ])->save();

        $this->auditLogger->record('students.password_reset', $request->user(), $request, $student, [
            'matric_no' => $student->matric_no,
        ]);

        return AjaxResponse::success($request, 'Student password reset to matric number.');
    }

    public function destroy(Request $request, Student $student): JsonResponse|RedirectResponse
    {
        abort_unless($request->user()?->can('students.update'), 403);

        $matricNo = $student->matric_no;
        $user = $student->user;

        DB::transaction(function () use ($student, $user): void {
            $student->assessments()->each(function ($assessment): void {
                $assessment->delete();
            });
            $student->supervisorAssignments()->delete();
            $student->payments()->delete();
            $student->placement()->delete();
            $student->tickets()->withTrashed()->forceDelete();
            $student->delete();
            $user?->delete();
        });

        $this->auditLogger->record('students.deleted', $request->user(), $request, metadata: [
            'matric_no' => $matricNo,
            'deletion' => 'permanent',
        ]);

        return AjaxResponse::success($request, 'Student permanently deleted.', route('admin.students.index', absolute: false));
    }

    public function template(Request $request, string $format): Response
    {
        abort_unless($request->user()?->can('students.import'), 403);
        abort_unless(in_array($format, ['csv', 'xlsx'], true), 404);

        $content = $this->studentImportService->template($format);
        $contentType = $format === 'xlsx'
            ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            : 'text/csv';

        return response($content, 200, [
            'Content-Type' => $contentType,
            'Content-Disposition' => "attachment; filename=student-import-template.{$format}",
        ]);
    }

    public function export(Request $request): Response
    {
        abort_unless($request->user()?->can('students.export'), 403);

        $handle = fopen('php://temp', 'w+');
        fputcsv($handle, ['Name', 'Email', 'Phone', 'Matric No', 'Faculty', 'Department', 'Level', 'Session', 'Status']);

        Student::query()
            ->with(['user', 'faculty', 'department', 'academicLevel', 'academicSession'])
            ->orderBy('matric_no')
            ->each(function (Student $student) use ($handle): void {
                fputcsv($handle, [
                    $student->user->name,
                    $student->user->email ?: 'N/A',
                    $student->user->phone,
                    $student->matric_no,
                    $student->faculty?->name ?? 'N/A',
                    $student->department?->name ?? 'N/A',
                    $student->academicLevel?->name ?? 'N/A',
                    $student->academicSession?->name ?? 'N/A',
                    $student->activation_status,
                ]);
            });

        rewind($handle);

        return response((string) stream_get_contents($handle), 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=students-export.csv',
        ]);
    }

    public function postingList(Request $request): Response
    {
        abort_unless($request->user()?->can('students.export'), 403);

        $validated = $request->validate([
            'academic_level_id' => ['nullable', 'integer', 'exists:academic_levels,id'],
            'state' => ['nullable', 'string', 'max:120'],
        ]);

        $placements = StudentPlacement::query()
            ->with(['student.user', 'student.department', 'academicSession'])
            ->when($validated['academic_level_id'] ?? null, fn ($query, int $levelId) => $query->where('academic_level_id', $levelId))
            ->when($validated['state'] ?? null, fn ($query, string $state) => $query->where('company_state', $state))
            ->whereHas('student')
            ->orderBy('company_state')
            ->orderBy('company_lga')
            ->orderBy(
                Student::query()
                    ->select('matric_no')
                    ->whereColumn('students.id', 'student_placements.student_id')
                    ->limit(1),
            )
            ->get();

        $year = (int) ($placements->first()?->siwes_year ?? StudentPlacement::query()->max('siwes_year') ?? now()->year);
        $session = $placements->first()?->academicSession?->name
            ?? AcademicSession::active()?->name
            ?? "{$year}/".($year + 1);

        $html = view('exports.student-posting-list', [
            'placements' => $placements,
            'year' => $year,
            'session' => $session,
        ])->render();

        return response($html, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=students_posting_LIST_{$year}.xls",
        ]);
    }
}
