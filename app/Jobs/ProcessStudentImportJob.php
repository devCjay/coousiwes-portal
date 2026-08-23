<?php

namespace App\Jobs;

use App\Models\StudentImport;
use App\Services\StudentImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessStudentImportJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $studentImportId) {}

    /**
     * Execute the job.
     */
    public function handle(StudentImportService $studentImportService): void
    {
        $import = StudentImport::query()->findOrFail($this->studentImportId);

        $studentImportService->process($import);
    }
}
