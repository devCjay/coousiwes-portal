<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\Assessment;
use App\Models\AssessmentRubricItem;
use App\Models\Supervisor;
use App\Services\AuditLogger;
use App\Services\GoogleVisionOcrService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AssignedStudentController extends Controller
{
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
            'Logbook Score',
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

    public function previewLogbookImport(Request $request, GoogleVisionOcrService $ocrService): View
    {
        $supervisor = $request->user()?->supervisor;
        abort_unless($supervisor instanceof Supervisor, 403);

        $validated = $request->validate([
            'score_sheet' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ]);

        try {
            $ocrText = $ocrService->extractText($validated['score_sheet']);
        } catch (\RuntimeException $exception) {
            return view('pages.supervisor.logbook-import-preview', [
                'supervisor' => $supervisor,
                'rows' => collect(),
                'ocrText' => '',
                'maxScore' => $this->logbookMaxScore(),
                'error' => $exception->getMessage(),
            ]);
        }

        $assignments = $supervisor->activeAssignments()
            ->with(['student.user', 'student.department', 'assessment'])
            ->latest('assigned_at')
            ->get();
        $rows = $this->buildLogbookPreviewRows($ocrText, $assignments);

        $request->session()->put('logbook_ocr_preview', [
            'rows' => $rows->map(fn (array $row): array => [
                'assignment_id' => $row['assignment_id'],
                'student_id' => $row['student_id'],
                'detected_score' => $row['detected_score'],
            ])->all(),
        ]);

        return view('pages.supervisor.logbook-import-preview', [
            'supervisor' => $supervisor,
            'rows' => $rows,
            'ocrText' => $ocrText,
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

        $preview = collect($request->session()->get('logbook_ocr_preview.rows', []))->keyBy('assignment_id');
        abort_if($preview->isEmpty(), 422, 'No OCR preview is available for confirmation.');

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
                        'source' => 'google_vision_logbook_import',
                        'reviewed_by_supervisor' => true,
                    ],
                ]);

                $auditLogger->record('assessments.logbook_ocr_imported', $request->user(), $request, $assessment, [
                    'student_id' => $assignment->student_id,
                    'score' => (int) $score,
                    'max_score' => $maxScore,
                ]);
                $saved++;
            }
        });

        $request->session()->forget('logbook_ocr_preview');

        return redirect()
            ->route('supervisor.students.index')
            ->with('toast_title', 'Logbook scores imported')
            ->with('status', "{$saved} score(s) saved, {$skipped} skipped after review.");
    }

    private function buildLogbookPreviewRows(string $ocrText, $assignments)
    {
        $lines = collect(preg_split('/\R+/', $ocrText) ?: [])
            ->map(fn (string $line): string => trim($line))
            ->filter()
            ->values();

        return $assignments->map(function ($assignment) use ($lines): array {
            $student = $assignment->student;
            $matchedLine = $lines->first(fn (string $line): bool => str_contains($this->normalizeToken($line), $this->normalizeToken($student->matric_no)));
            $detectedScore = $matchedLine ? $this->extractScoreFromLine($matchedLine) : null;

            return [
                'assignment_id' => $assignment->id,
                'student_id' => $student->id,
                'student_name' => $student->user?->name ?? 'N/A',
                'matric_no' => $student->matric_no,
                'department' => $student->department?->name ?? 'N/A',
                'detected_score' => $detectedScore,
                'matched_line' => $matchedLine,
                'status' => $assignment->assessment ? 'Already graded' : ($detectedScore === null ? 'Needs review' : 'Detected'),
                'locked' => $assignment->assessment !== null,
            ];
        });
    }

    private function extractScoreFromLine(string $line): ?int
    {
        preg_match_all('/\b\d{1,3}\b/', $line, $matches);
        $scores = collect($matches[0] ?? [])
            ->map(fn (string $value): int => (int) ltrim($value, '0'))
            ->filter(fn (int $value): bool => $value >= 0 && $value <= $this->logbookMaxScore())
            ->values();

        return $scores->last();
    }

    private function normalizeToken(string $value): string
    {
        return preg_replace('/[^A-Z0-9]/', '', strtoupper($value)) ?: '';
    }

    private function logbookMaxScore(): int
    {
        return max(1, (int) AppSetting::value('ocr.logbook_max_score', 100));
    }
}
