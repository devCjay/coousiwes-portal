<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSupervisorRequest;
use App\Http\Requests\Admin\UpdateSupervisorRequest;
use App\Models\AcademicLevel;
use App\Models\AcademicSession;
use App\Models\Assessment;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Student;
use App\Models\StudentPlacement;
use App\Models\Supervisor;
use App\Models\SupervisorStudentAssignment;
use App\Services\AuditLogger;
use App\Services\SupervisorManager;
use App\Support\AjaxResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class SupervisorController extends Controller
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly SupervisorManager $supervisorManager,
    ) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()?->can('supervisors.view'), 403);

        $assignmentSearch = $request->string('assignment_search')->toString();
        $analyticsYear = $request->integer('year') ?: null;
        $supervisorSearch = $request->string('search')->toString();

        $metrics = $this->supervisorMetrics($analyticsYear);
        $filteredMetrics = $this->filterSupervisorMetrics($metrics, $supervisorSearch)
            ->sortByDesc('performance_score')
            ->values();
        $supervisors = $this->paginateMetrics($filteredMetrics, $request);

        return view('pages.admin.supervisors', [
            'supervisors' => $supervisors,
            'supervisorMetrics' => $filteredMetrics,
            'chartMetrics' => $filteredMetrics->take(10)->values(),
            'analyticsYears' => $this->analyticsYears(),
            'analyticsYear' => $analyticsYear,
            'summary' => [
                'total_supervisors' => $metrics->count(),
                'students_assigned' => $metrics->sum('students_assigned'),
                'assessments' => $metrics->sum('assessments'),
                'average_performance' => round((float) $metrics->avg('performance_score'), 1),
            ],
            'allSupervisors' => Supervisor::query()->with('user')->where('status', Supervisor::STATUS_ACTIVE)->orderBy('staff_no')->get(),
            'students' => Student::query()->with(['user', 'department', 'activeSupervisorAssignment.supervisor.user'])->latest()->limit(200)->get(),
            'faculties' => Faculty::query()->orderBy('name')->get(),
            'departments' => Department::query()->with('faculty')->orderBy('name')->get(),
            'levels' => AcademicLevel::query()->orderBy('level')->get(),
            'sessions' => AcademicSession::query()->orderByDesc('starts_on')->get(),
            'states' => config('siwes_profile.states', []),
            'placementStates' => StudentPlacement::query()
                ->whereNotNull('company_state')
                ->distinct()
                ->orderBy('company_state')
                ->pluck('company_state'),
            'placementLgas' => StudentPlacement::query()
                ->whereNotNull('company_lga')
                ->select('company_state', 'company_lga')
                ->distinct()
                ->orderBy('company_state')
                ->orderBy('company_lga')
                ->get(),
            'assignments' => SupervisorStudentAssignment::query()
                ->with(['supervisor.user', 'student.user', 'student.placement'])
                ->whereNull('revoked_at')
                ->when($assignmentSearch !== '', function ($query) use ($assignmentSearch): void {
                    $query->where(function ($innerQuery) use ($assignmentSearch): void {
                        $innerQuery
                            ->whereHas('student.user', fn ($userQuery) => $userQuery->where('name', 'like', "%{$assignmentSearch}%"))
                            ->orWhereHas('student', fn ($studentQuery) => $studentQuery->where('matric_no', 'like', "%{$assignmentSearch}%"))
                            ->orWhereHas('student.placement', fn ($placementQuery) => $placementQuery->where('siwes_year', 'like', "%{$assignmentSearch}%"))
                            ->orWhereHas('supervisor.user', fn ($userQuery) => $userQuery->where('name', 'like', "%{$assignmentSearch}%"));
                    });
                })
                ->latest('assigned_at')
                ->paginate(25)
                ->withQueryString(),
        ]);
    }

    public function store(StoreSupervisorRequest $request): JsonResponse|RedirectResponse
    {
        $supervisor = $this->supervisorManager->create($request->validated());

        $this->auditLogger->record('supervisors.created', $request->user(), $request, $supervisor, $supervisor->only(['staff_no', 'status']));

        return AjaxResponse::success($request, 'Supervisor created.');
    }

    public function update(UpdateSupervisorRequest $request, Supervisor $supervisor): JsonResponse|RedirectResponse
    {
        $before = $supervisor->only(['staff_no', 'status']);
        $supervisor = $this->supervisorManager->update($supervisor, $request->validated());

        $this->auditLogger->record('supervisors.updated', $request->user(), $request, $supervisor, [
            'before' => $before,
            'after' => $supervisor->only(['staff_no', 'status']),
        ]);

        return AjaxResponse::success($request, 'Supervisor updated.');
    }

    public function show(Request $request, Supervisor $supervisor): View
    {
        abort_unless($request->user()?->can('supervisors.view'), 403);

        return view('pages.admin.supervisor-show', [
            'supervisor' => $supervisor->load(['user', 'assignments.student.user', 'assignments.student.department']),
        ]);
    }

    public function suspend(Request $request, Supervisor $supervisor): JsonResponse|RedirectResponse
    {
        abort_unless($request->user()?->can('supervisors.suspend'), 403);

        $supervisor = $this->supervisorManager->changeStatus($supervisor, Supervisor::STATUS_SUSPENDED);
        $this->auditLogger->record('supervisors.suspended', $request->user(), $request, $supervisor);

        return AjaxResponse::success($request, 'Supervisor suspended.');
    }

    public function reactivate(Request $request, Supervisor $supervisor): JsonResponse|RedirectResponse
    {
        abort_unless($request->user()?->can('supervisors.update'), 403);

        $supervisor = $this->supervisorManager->changeStatus($supervisor, Supervisor::STATUS_ACTIVE);
        $this->auditLogger->record('supervisors.reactivated', $request->user(), $request, $supervisor);

        return AjaxResponse::success($request, 'Supervisor reactivated.');
    }

    public function export(Request $request): Response
    {
        abort_unless($request->user()?->can('supervisors.view'), 403);

        $handle = fopen('php://temp', 'w+');
        fputcsv($handle, ['Rank', 'Name', 'Email', 'Staff No', 'Students', 'Assessments', 'Feedback', 'Months', 'Performance Score (%)', 'Rating', 'Payment']);

        $year = $request->integer('year') ?: null;

        $this->supervisorMetrics($year)
            ->sortByDesc('performance_score')
            ->values()
            ->each(function (array $supervisor, int $index) use ($handle): void {
            fputcsv($handle, [
                $index + 1,
                $supervisor['name'],
                $supervisor['email'],
                $supervisor['staff_no'],
                $supervisor['students_assigned'],
                $supervisor['assessments'],
                $supervisor['feedback'],
                $supervisor['months'],
                $supervisor['performance_score'],
                $supervisor['rating'],
                $supervisor['payment'],
            ]);
        });

        rewind($handle);

        return response((string) stream_get_contents($handle), 200, [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename=supervisor-analytics.xls',
        ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function supervisorMetrics(?int $year = null): Collection
    {
        return Supervisor::query()
            ->with([
                'user',
                'activeAssignments.student.placement',
                'assessments' => fn ($query) => $query
                    ->when($year, fn ($yearQuery) => $yearQuery->whereYear('submitted_at', $year)),
            ])
            ->get()
            ->map(function (Supervisor $supervisor) use ($year): array {
                $assignments = $supervisor->activeAssignments;

                if ($year) {
                    $assignments = $assignments->filter(
                        fn (SupervisorStudentAssignment $assignment): bool => (int) ($assignment->student?->placement?->siwes_year ?? 0) === $year
                    );
                }

                $assessments = $supervisor->assessments;
                $maxScore = (int) $assessments->sum('max_score');
                $performance = $maxScore > 0
                    ? round(((int) $assessments->sum('total_score') / $maxScore) * 100, 1)
                    : 0.0;
                $metadata = $supervisor->metadata ?? [];

                return [
                    'id' => $supervisor->id,
                    'name' => $supervisor->user?->name ?? 'N/A',
                    'email' => $supervisor->user?->email ?? 'N/A',
                    'staff_no' => $supervisor->staff_no,
                    'students_assigned' => $assignments->count(),
                    'assessments' => $assessments->count(),
                    'feedback' => $assessments->filter(fn (Assessment $assessment): bool => filled($assessment->feedback))->count(),
                    'months' => $assignments
                        ->map(fn (SupervisorStudentAssignment $assignment): ?string => $assignment->assigned_at?->format('Y-m'))
                        ->filter()
                        ->unique()
                        ->count(),
                    'performance_score' => $performance,
                    'rating' => $this->ratingForPerformance($performance),
                    'payment' => $metadata['payment_status']
                        ?? ($metadata['bank_name'] ?? null ? 'Configured' : 'N/A'),
                    'bank_name' => $metadata['bank_name'] ?? '',
                    'account_name' => $metadata['account_name'] ?? '',
                    'account_number' => $metadata['account_number'] ?? '',
                    'show_url' => route('admin.supervisors.show', $supervisor),
                    'status' => $supervisor->status,
                ];
            });
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $metrics
     * @return Collection<int, array<string, mixed>>
     */
    private function filterSupervisorMetrics(Collection $metrics, string $search): Collection
    {
        if ($search === '') {
            return $metrics;
        }

        $needle = mb_strtolower($search);

        return $metrics->filter(function (array $supervisor) use ($needle): bool {
            return collect([
                $supervisor['name'],
                $supervisor['email'],
                $supervisor['staff_no'],
                $supervisor['bank_name'],
                $supervisor['account_name'],
                $supervisor['account_number'],
            ])->contains(fn (mixed $value): bool => str_contains(mb_strtolower((string) $value), $needle));
        });
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $metrics
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    private function paginateMetrics(Collection $metrics, Request $request): LengthAwarePaginator
    {
        $perPage = 25;
        $page = max((int) $request->query('page', 1), 1);

        return new LengthAwarePaginator(
            $metrics->forPage($page, $perPage)->values(),
            $metrics->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
    }

    /**
     * @return Collection<int, int>
     */
    private function analyticsYears(): Collection
    {
        return StudentPlacement::query()
            ->whereNotNull('siwes_year')
            ->distinct()
            ->orderByDesc('siwes_year')
            ->pluck('siwes_year')
            ->push((int) now()->year)
            ->map(fn (mixed $year): int => (int) $year)
            ->unique()
            ->sortDesc()
            ->values();
    }

    private function ratingForPerformance(float $performance): string
    {
        return match (true) {
            $performance >= 85 => 'Excellent',
            $performance >= 70 => 'Good',
            $performance >= 50 => 'Average',
            $performance > 0 => 'Needs Review',
            default => 'N/A',
        };
    }
}
