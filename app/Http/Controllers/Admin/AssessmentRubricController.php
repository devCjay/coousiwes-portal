<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAssessmentRubricItemRequest;
use App\Models\Assessment;
use App\Models\AssessmentRubricItem;
use App\Services\AuditLogger;
use App\Support\AjaxResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssessmentRubricController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()?->can('settings.view'), 403);

        return view('pages.admin.assessment-rubric', [
            'rubricItems' => AssessmentRubricItem::query()->orderBy('sort_order')->orderBy('name')->get(),
            'recentAssessments' => Assessment::query()
                ->with(['student.user', 'supervisor.user'])
                ->latest('submitted_at')
                ->limit(10)
                ->get(),
        ]);
    }

    public function store(StoreAssessmentRubricItemRequest $request): JsonResponse|RedirectResponse
    {
        $rubricItem = AssessmentRubricItem::query()->create($this->payload($request));

        $this->auditLogger->record('assessments.rubric_created', $request->user(), $request, $rubricItem);

        return AjaxResponse::success($request, 'Rubric item created.', reload: true);
    }

    public function update(StoreAssessmentRubricItemRequest $request, AssessmentRubricItem $assessmentRubricItem): JsonResponse|RedirectResponse
    {
        $before = $assessmentRubricItem->only(['name', 'max_score', 'weight', 'is_active']);
        $assessmentRubricItem->update($this->payload($request));

        $this->auditLogger->record('assessments.rubric_updated', $request->user(), $request, $assessmentRubricItem, [
            'before' => $before,
            'after' => $assessmentRubricItem->only(['name', 'max_score', 'weight', 'is_active']),
        ]);

        return AjaxResponse::success($request, 'Rubric item updated.', reload: true);
    }

    /**
     * @return array{name: string, description: string|null, max_score: int, weight: int, sort_order: int, is_active: bool}
     */
    private function payload(StoreAssessmentRubricItemRequest $request): array
    {
        $validated = $request->validated();

        return [
            'name' => (string) $validated['name'],
            'description' => $validated['description'] ?? null,
            'max_score' => (int) $validated['max_score'],
            'weight' => (int) $validated['weight'],
            'sort_order' => (int) $validated['sort_order'],
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ];
    }
}
