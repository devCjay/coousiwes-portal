<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentRubricItem;
use App\Models\Supervisor;
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

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Assigned Students');
        $sheet->fromArray([
            'Student Name',
            'Reg No',
            'Department',
            'Placement Address',
            'LGA',
            'State',
            'Logbook Score (40)',
        ], null, 'A1');

        $assignments->values()->each(function ($assignment, int $index) use ($sheet): void {
            $student = $assignment->student;
            $placement = $student?->placement;

            $sheet->fromArray([
                $student?->user?->name ?? 'N/A',
                $student?->matric_no ?? 'N/A',
                $student?->department?->name ?? 'N/A',
                $placement?->company_address ?: 'N/A',
                $placement?->company_lga ?: 'N/A',
                $placement?->company_state ?: 'N/A',
                '',
            ], null, 'A'.($index + 2));
        });

        foreach (range('A', 'G') as $column) {
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
            $importedRows = $this->readLogbookScoresFromSpreadsheet($validated['score_sheet']->getRealPath());
        } catch (\Throwable $exception) {
            return view('pages.supervisor.logbook-import-preview', [
                'supervisor' => $supervisor,
                'rows' => collect(),
                'importedRows' => collect(),
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
                'detected_score' => $row['detected_score'],
            ])->all(),
        ]);

        return view('pages.supervisor.logbook-import-preview', [
            'supervisor' => $supervisor,
            'rows' => $rows,
            'importedRows' => $importedRows,
            'maxScore' => $this->logbookMaxScore(),
            'error' => null,
        ]);
    }

    public function confirmLogbookImport(Request $request, AuditLogger $auditLogger)
    {
        $supervisor = $request->user()?->supervisor;
        abort_unless($supervisor instanceof Supervisor, 403);

        $validated = $request->validate([
            'scores' => ['required', 'array'],
            'scores.*' => ['nullable', 'integer', 'min:0', 'max:'.$this->logbookMaxScore()],
        ]);

        $preview = collect($request->session()->get('logbook_excel_preview.rows', []))->keyBy('assignment_id');
        abort_if($preview->isEmpty(), 422, 'No spreadsheet preview is available for confirmation.');

        $saved = 0;
        $skipped = 0;
        $maxScore = $this->logbookMaxScore();

        DB::transaction(function () use ($validated, $preview, $supervisor, $request, $auditLogger, $maxScore, &$saved, &$skipped): void {
            foreach ($validated['scores'] as $assignmentId => $score) {
                if ($score === null || $score === '') {
                    $skipped++;
                    continue;
                }

                if (! $preview->has((int) $assignmentId)) {
                    $skipped++;
                    continue;
                }

                $assignment = $supervisor->activeAssignments()
                    ->with(['student.user'])
                    ->whereKey((int) $assignmentId)
                    ->first();

                if (! $assignment || $assignment->assessment()->exists()) {
                    $skipped++;
                    continue;
                }

                $assessment = Assessment::query()->create([
                    'supervisor_id' => $supervisor->id,
                    'student_id' => $assignment->student_id,
                    'supervisor_student_assignment_id' => $assignment->id,
                    'total_score' => (int) $score,
                    'max_score' => $maxScore,
                    'status' => Assessment::STATUS_SUBMITTED,
                    'feedback' => 'Imported from reviewed printed logbook score sheet.',
                    'submitted_at' => now(),
                    'metadata' => [
                        'source' => 'excel_logbook_import',
                        'reviewed_by_supervisor' => true,
                    ],
                ]);

                $auditLogger->record('assessments.logbook_excel_imported', $request->user(), $request, $assessment, [
                    'student_id' => $assignment->student_id,
                    'score' => (int) $score,
                    'max_score' => $maxScore,
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

    private function readLogbookScoresFromSpreadsheet(string $path)
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
        $scoreColumn = $headers->get('logbookscore40') ?? $headers->get('logbookscore') ?? $headers->get('score') ?? 'G';

        return $rows->skip(1)
            ->map(function (array $row, int $index) use ($regColumn, $scoreColumn): array {
                $regNo = trim((string) ($row[$regColumn] ?? ''));
                $score = trim((string) ($row[$scoreColumn] ?? ''));

                return [
                    'row_number' => $index + 2,
                    'matric_no' => $regNo,
                    'score' => $this->normalizeScore($score),
                    'raw_score' => $score,
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
            $detectedScore = $matchedRow['score'] ?? null;

            return [
                'assignment_id' => $assignment->id,
                'student_id' => $student->id,
                'student_name' => $student->user?->name ?? 'N/A',
                'matric_no' => $student->matric_no,
                'department' => $student->department?->name ?? 'N/A',
                'detected_score' => $detectedScore,
                'matched_line' => $matchedRow ? "Row {$matchedRow['row_number']} / {$matchedRow['matric_no']} / {$matchedRow['raw_score']}" : null,
                'status' => $assignment->assessment ? 'Already graded' : ($detectedScore === null ? 'Needs review' : 'Detected'),
                'locked' => $assignment->assessment !== null,
            ];
        });
    }

    private function normalizeScore(string $value): ?int
    {
        if ($value === '' || ! is_numeric($value)) {
            return null;
        }

        $score = (int) $value;

        return $score >= 0 && $score <= $this->logbookMaxScore() ? $score : null;
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
        return self::LOGBOOK_MAX_SCORE;
    }
}
