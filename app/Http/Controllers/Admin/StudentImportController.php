<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PreviewStudentImportRequest;
use App\Models\AppSetting;
use App\Models\StudentImport;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\StudentImportService;
use App\Support\AjaxResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StudentImportController extends Controller
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly StudentImportService $studentImportService,
    ) {}

    public function preview(PreviewStudentImportRequest $request): JsonResponse
    {
        $file = $request->file('students_file');
        $import = $this->studentImportService->createImport(
            $file,
            $request->user() instanceof User ? (int) $request->user()->id : null,
            $request->boolean('auto_activate', false),
            $request->boolean('workshop_fee_paid', false),
        );

        $this->auditLogger->record('students.import_previewed', $request->user(), $request, $import, [
            'filename' => $import->original_filename,
            'total_rows' => $import->total_rows,
            'failed_rows' => $import->failed_rows,
        ]);

        return response()->json([
            'message' => $import->failed_rows === 0 ? 'Import preview is ready.' : 'Import preview contains row errors.',
            'total' => $import->total_rows,
            'preview' => $import->preview_rows,
            'errors' => $import->error_report,
            'auto_activate' => $import->auto_activate_students,
            'workshop_fee_paid' => $import->mark_workshop_fee_paid,
            'import_id' => $import->id,
            'process_url' => route('admin.students.imports.process', $import),
        ]);
    }

    public function store(PreviewStudentImportRequest $request): JsonResponse|RedirectResponse
    {
        $file = $request->file('students_file');
        $import = $this->studentImportService->createImport(
            $file,
            $request->user() instanceof User ? (int) $request->user()->id : null,
            $request->boolean('auto_activate', false),
            $request->boolean('workshop_fee_paid', false),
        );

        $this->auditLogger->record('students.import_previewed', $request->user(), $request, $import, [
            'filename' => $import->original_filename,
            'total_rows' => $import->total_rows,
            'failed_rows' => $import->failed_rows,
        ]);

        return AjaxResponse::success($request, 'Student import preview saved.');
    }

    public function process(Request $request, StudentImport $studentImport): JsonResponse|RedirectResponse
    {
        abort_unless($request->user()?->can('students.import'), 403);

        if ($studentImport->status === StudentImport::STATUS_COMPLETED) {
            return AjaxResponse::error($request, 'This student import has already been processed.');
        }

        $threshold = (int) AppSetting::value('imports.immediate_threshold', config('siwes.imports.immediate_threshold', 2000));

        if ($studentImport->total_rows > $threshold) {
            $studentImport->update(['status' => StudentImport::STATUS_QUEUED]);

            $this->auditLogger->record('students.import_queued', $request->user(), $request, $studentImport);

            return AjaxResponse::success(
                $request,
                "Large import detected ({$studentImport->total_rows} rows). It has been queued for cron processing.",
            );
        }

        $processedImport = $this->studentImportService->process($studentImport);

        $this->auditLogger->record('students.import_processed', $request->user(), $request, $processedImport, [
            'successful_rows' => $processedImport->successful_rows,
            'failed_rows' => $processedImport->failed_rows,
        ]);

        return AjaxResponse::success(
            $request,
            "Student import completed. {$processedImport->successful_rows} students created, {$processedImport->failed_rows} failed.",
        );
    }
}
