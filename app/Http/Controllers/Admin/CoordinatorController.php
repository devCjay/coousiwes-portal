<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCoordinatorRequest;
use App\Models\AcademicLevel;
use App\Models\AcademicSession;
use App\Models\Assessment;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\SiwesCoordinator;
use App\Models\Student;
use App\Services\AuditLogger;
use App\Services\CoordinatorManager;
use App\Support\AjaxResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CoordinatorController extends Controller
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly CoordinatorManager $coordinatorManager,
    ) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()?->can('coordinators.view'), 403);

        $coordinators = SiwesCoordinator::query()
            ->with(['user', 'faculty', 'department'])
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('pages.admin.coordinators', [
            'coordinators' => $coordinators,
            'summary' => [
                'total' => SiwesCoordinator::query()->count(),
                'active' => SiwesCoordinator::query()->where('status', SiwesCoordinator::STATUS_ACTIVE)->count(),
                'departments' => SiwesCoordinator::query()->distinct('department_id')->count('department_id'),
                'students' => Student::query()->whereIn('department_id', SiwesCoordinator::query()->select('department_id'))->count(),
                'assessments' => Assessment::query()->whereHas('student', fn ($query) => $query->whereIn('department_id', SiwesCoordinator::query()->select('department_id')))->count(),
            ],
            'faculties' => Faculty::query()->where('is_active', true)->orderBy('name')->get(),
            'departments' => Department::query()->where('is_active', true)->with('faculty')->orderBy('name')->get(),
            'levels' => AcademicLevel::query()->where('is_active', true)->orderBy('level')->get(),
            'sessions' => AcademicSession::query()->orderByDesc('starts_on')->get(),
        ]);
    }

    public function store(StoreCoordinatorRequest $request): JsonResponse|RedirectResponse
    {
        $coordinator = $this->coordinatorManager->create($request->validated());

        $this->auditLogger->record('coordinators.created', $request->user(), $request, $coordinator, [
            'department_id' => $coordinator->department_id,
            'staff_no' => $coordinator->staff_no,
        ]);

        return AjaxResponse::success($request, 'SIWES coordinator created.');
    }

    public function destroy(Request $request, SiwesCoordinator $coordinator): JsonResponse|RedirectResponse
    {
        abort_unless($request->user()?->can('coordinators.update'), 403);

        $coordinatorId = $coordinator->id;
        $this->coordinatorManager->delete($coordinator);

        $this->auditLogger->record('coordinators.deleted', $request->user(), $request, metadata: [
            'coordinator_id' => $coordinatorId,
        ]);

        return AjaxResponse::success($request, 'SIWES coordinator deleted.', route('admin.coordinators.index'));
    }
}
