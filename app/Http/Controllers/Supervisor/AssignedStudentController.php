<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentRubricItem;
use App\Models\Supervisor;
use App\Services\AssessmentService;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AssignedStudentController extends Controller
{
    private const LOGBOOK_MAX_SCORE = 40;

    public function index(Request $request): View
    {
        $supervisor = $request->user()?->supervisor;
        abort_unless($supervisor instanceof Supervisor, 403);

        return view('pages.supervisor.students', [
            'supervisor' => $supervisor->load('user'),
            'assignments' => $supervisor->activeAssignments()
                ->with([
                    'student.user',
                    'student.faculty',
                    'student.department',
                    'student.academicLevel',
                    'student.academicSession',
                    'student.placement.academicLevel',
                    'student.placement.academicSession',
                    'assessment.scores.rubricItem',
                ])
                ->latest('assigned_at')
                ->paginate(20)
                ->withQueryString(),
            'rubricItems' => AssessmentRubricItem::query()->where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $supervisor = $request->user()?->supervisor;
        abort_unless($supervisor instanceof Supervisor, 403);

        $assignments = $supervisor->activeAssignments()
            ->with([
                'student.user',
                'student.department',
                'student.placement',
                'assessment',
            ])
            ->latest('assigned_at')
            ->get();
        $rubricItems = $this->activeRubricItems();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Assigned Students');
        $headers = collect([
            "Student's Name",
            'Reg No',
            "Student's Phone Number",
            "Company's Name",
            "Company Supervisor's Phone Number",
            "Company's Address, LGA, State",
            'Department',
        ])
            ->merge($rubricItems->map(fn (AssessmentRubricItem $item): string => "{$item->name} ({$item->max_score} Marks)"))
            ->push('Total ('.$this->logbookMaxScore().' Marks)')
            ->all();

        $sheet->fromArray($headers, null, 'A1');

        $assignments->values()->each(function ($assignment, int $index) use ($sheet, $rubricItems): void {
            $student = $assignment->student;
            $placement = $student?->placement;

            $row = [
                $student?->user?->name ?? 'N/A',
                $student?->matric_no ?? 'N/A',
                $student?->user?->phone ?: 'N/A',
                $placement?->company_name ?: 'N/A',
                $placement?->company_supervisor_phone ?: 'N/A',
                $placement ? collect([$placement->company_address, $placement->company_lga, $placement->company_state])->filter()->join(', ') : 'N/A',
                $student?->department?->name ?? 'N/A',
            ];

            $row = array_merge($row, array_fill(0, $rubricItems->count(), ''), ['']);
            $sheet->fromArray($row, null, 'A'.($index + 2));
        });

        foreach (range('A', $sheet->getHighestColumn()) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $path = tempnam(sys_get_temp_dir(), 'assigned-students-');
        (new Xlsx($spreadsheet))->save($path);

        return response()->download($path, 'assigned-students-list.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend();
    }

    public function previewLogbookImport(Request $request): View
    {
        $supervisor = $request->user()?->supervisor;
        abort_unless($supervisor instanceof Supervisor, 403);

        $validated = $request->validate([
            'score_sheet' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:8192'],
        ]);

        try {
            $rubricItems = $this->activeRubricItems();
            $importedRows = $this->readLogbookScoresFromSpreadsheet($validated['score_sheet']->getRealPath(), $rubricItems);
        } catch (\Throwable $exception) {
            return view('pages.supervisor.logbook-import-preview', [
                'supervisor' => $supervisor,
                'rows' => collect(),
                'importedRows' => collect(),
                'rubricItems' => collect(),
                'maxScore' => $this->logbookMaxScore(),
                'error' => $exception->getMessage(),
            ]);
        }

        $assignments = $supervisor->activeAssignments()
            ->with(['student.user', 'student.department', 'assessment'])
            ->latest('assigned_at')
            ->get();
        $rows = $this->buildLogbookPreviewRows($importedRows, $assignments);

        $request->session()->put('logbook_excel_preview', [
            'rows' => $rows->map(fn (array $row): array => [
                'assignment_id' => $row['assignment_id'],
                'student_id' => $row['student_id'],
                'rubric_scores' => $row['rubric_scores'],
            ])->all(),
        ]);

        return view('pages.supervisor.logbook-import-preview', [
            'supervisor' => $supervisor,
            'rows' => $rows,
            'importedRows' => $importedRows,
            'rubricItems' => $rubricItems,
            'maxScore' => $this->logbookMaxScore(),
            'error' => null,
        ]);
    }

    public function confirmLogbookImport(Request $request, AuditLogger $auditLogger, AssessmentService $assessmentService)
    {
        $supervisor = $request->user()?->supervisor;
        abort_unless($supervisor instanceof Supervisor, 403);

        $validated = $request->validate([
            'scores' => ['required', 'array'],
            'scores.*' => ['required', 'array'],
            'scores.*.*' => ['nullable', 'integer', 'min:0'],
        ]);

        $preview = collect($request->session()->get('logbook_excel_preview.rows', []))->keyBy('assignment_id');
        abort_if($preview->isEmpty(), 422, 'No spreadsheet preview is available for confirmation.');

        $saved = 0;
        $skipped = 0;
        $rubricItems = $this->activeRubricItems()->keyBy('id');

        DB::transaction(function () use ($validated, $preview, $supervisor, $request, $auditLogger, $assessmentService, $rubricItems, &$saved, &$skipped): void {
            foreach ($validated['scores'] as $assignmentId => $scores) {
                if (! $preview->has((int) $assignmentId)) {
                    $skipped++;
                    continue;
                }

                $scores = collect($scores)
                    ->mapWithKeys(fn ($score, string|int $rubricId): array => [(int) $rubricId => $score === null || $score === '' ? null : (int) $score])
                    ->all();

                if (collect($scores)->filter(fn ($score): bool => $score !== null)->isEmpty()) {
                    $skipped++;
                    continue;
                }

                $assignment = $supervisor->activeAssignments()
                    ->with(['student.user'])
                    ->whereKey((int) $assignmentId)
                    ->first();

                $hasMissingScore = $rubricItems->keys()->contains(fn (int $id): bool => ! array_key_exists($id, $scores) || $scores[$id] === null);
                $hasInvalidScore = collect($scores)->contains(function (?int $score, int $rubricId) use ($rubricItems): bool {
                    $rubric = $rubricItems->get($rubricId);

                    return ! $rubric || $score === null || $score < 0 || $score > $rubric->max_score;
                });

                if (! $assignment || $assignment->assessment()->exists() || $hasMissingScore || $hasInvalidScore) {
                    $skipped++;
                    continue;
                }

                try {
                    $assessment = $assessmentService->submit(
                        $supervisor,
                        $assignment->student,
                        $scores,
                        'Imported from reviewed printed logbook score sheet.',
                    );
                } catch (\RuntimeException) {
                    $skipped++;
                    continue;
                }

                $assessment->update([
                    'metadata' => [
                        'source' => 'excel_logbook_import',
                        'reviewed_by_supervisor' => true,
                    ],
                ]);

                $auditLogger->record('assessments.logbook_excel_imported', $request->user(), $request, $assessment, [
                    'student_id' => $assignment->student_id,
                    'score' => $assessment->total_score,
                    'max_score' => $assessment->max_score,
                ]);
                $saved++;
            }
        });

        $request->session()->forget('logbook_excel_preview');

        return redirect()
            ->route('supervisor.students.index')
            ->with('toast_title', 'Logbook scores imported')
            ->with('status', "{$saved} score(s) saved, {$skipped} skipped after review.");
    }

    private function readLogbookScoresFromSpreadsheet(string $path, $rubricItems)
    {
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = collect($sheet->toArray(null, true, true, true))
            ->filter(fn (array $row): bool => collect($row)->filter(fn ($value): bool => trim((string) $value) !== '')->isNotEmpty())
            ->values();

        if ($rows->isEmpty()) {
            throw new \RuntimeException('The uploaded score sheet is empty.');
        }

        $headers = collect($rows->first())
            ->mapWithKeys(fn ($value, string $column): array => [$this->normalizeHeader((string) $value) => $column]);

        $regColumn = $headers->get('regno') ?? $headers->get('matricnumber') ?? $headers->get('matricno') ?? 'B';
        $rubricColumns = $rubricItems->mapWithKeys(function (AssessmentRubricItem $item) use ($headers): array {
            $normalized = $this->normalizeHeader("{$item->name} {$item->max_score} Marks");
            $fallback = $this->normalizeHeader($item->name);

            return [$item->id => [
                'column' => $headers->get($normalized) ?? $headers->get($fallback),
                'max_score' => $item->max_score,
            ]];
        });

        return $rows->skip(1)
            ->map(function (array $row, int $index) use ($regColumn, $rubricColumns): array {
                $regNo = trim((string) ($row[$regColumn] ?? ''));
                $rubricScores = $rubricColumns->mapWithKeys(function (array $config, int $rubricId) use ($row): array {
                    $column = $config['column'];
                    $rawScore = $column ? trim((string) ($row[$column] ?? '')) : '';

                    return [$rubricId => [
                        'score' => $this->normalizeScore($rawScore, (int) $config['max_score']),
                        'raw_score' => $rawScore,
                    ]];
                })->all();

                return [
                    'row_number' => $index + 2,
                    'matric_no' => $regNo,
                    'rubric_scores' => $rubricScores,
                    'total_score' => collect($rubricScores)->sum('score'),
                ];
            })
            ->filter(fn (array $row): bool => $row['matric_no'] !== '')
            ->values();
    }

    private function buildLogbookPreviewRows($importedRows, $assignments)
    {
        $scoresByRegNo = collect($importedRows)
            ->keyBy(fn (array $row): string => $this->normalizeToken($row['matric_no']));

        return $assignments->map(function ($assignment) use ($scoresByRegNo): array {
            $student = $assignment->student;
            $matchedRow = $scoresByRegNo->get($this->normalizeToken($student->matric_no));
            $rubricScores = collect($matchedRow['rubric_scores'] ?? [])
                ->mapWithKeys(fn (array $score, int $rubricId): array => [$rubricId => $score['score']])
                ->all();
            $detectedScore = collect($rubricScores)->sum();
            $hasMissingScore = collect($rubricScores)->contains(fn ($score): bool => $score === null);

            return [
                'assignment_id' => $assignment->id,
                'student_id' => $student->id,
                'student_name' => $student->user?->name ?? 'N/A',
                'matric_no' => $student->matric_no,
                'department' => $student->department?->name ?? 'N/A',
                'detected_score' => $detectedScore,
                'rubric_scores' => $rubricScores,
                'matched_line' => $matchedRow ? "Row {$matchedRow['row_number']} / {$matchedRow['matric_no']} / total {$matchedRow['total_score']}" : null,
                'status' => $assignment->assessment ? 'Already graded' : (! $matchedRow || $hasMissingScore ? 'Needs review' : 'Detected'),
                'locked' => $assignment->assessment !== null,
            ];
        });
    }

    private function normalizeScore(string $value, ?int $maxScore = null): ?int
    {
        if ($value === '' || ! is_numeric($value)) {
            return null;
        }

        $score = (int) $value;
        $maxScore ??= $this->logbookMaxScore();

        return $score >= 0 && $score <= $maxScore ? $score : null;
    }

    private function normalizeHeader(string $value): string
    {
        return preg_replace('/[^a-z0-9]/', '', strtolower($value)) ?: '';
    }

    private function normalizeToken(string $value): string
    {
        return preg_replace('/[^A-Z0-9]/', '', strtoupper($value)) ?: '';
    }

    private function logbookMaxScore(): int
    {
        return (int) $this->activeRubricItems()->sum('max_score') ?: self::LOGBOOK_MAX_SCORE;
    }

    private function activeRubricItems()
    {
        return AssessmentRubricItem::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }
}
