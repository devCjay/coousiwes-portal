<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PreviewStudentImportRequest;
use App\Jobs\ProcessStudentImportJob;
use App\Models\StudentImport;
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
        $import = $this->studentImportService->createImport($file, (int) $request->user()->id);

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
            'import_id' => $import->id,
            'process_url' => route('admin.students.imports.process', $import),
        ]);
    }

    public function store(PreviewStudentImportRequest $request): JsonResponse|RedirectResponse
    {
        $file = $request->file('students_file');
        $import = $this->studentImportService->createImport($file, (int) $request->user()->id);

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

        $studentImport->update(['status' => StudentImport::STATUS_QUEUED]);
        ProcessStudentImportJob::dispatch($studentImport->id);

        $this->auditLogger->record('students.import_queued', $request->user(), $request, $studentImport);

        return AjaxResponse::success($request, 'Student import queued for processing.');
    }
}
