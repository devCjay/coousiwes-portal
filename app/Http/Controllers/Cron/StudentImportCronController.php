<?php

namespace App\Http\Controllers\Cron;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Services\StudentImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentImportCronController extends Controller
{
    public function __invoke(Request $request, StudentImportService $studentImportService): JsonResponse
    {
        $configuredToken = trim((string) AppSetting::value('imports.cron_token', config('siwes.imports.cron_token')));
        $requestToken = (string) $request->query('token', '');

        if ($configuredToken === '') {
            return response()->json([
                'message' => 'Student import cron token is not configured.',
            ], 403);
        }

        if (! hash_equals($configuredToken, $requestToken)) {
            return response()->json([
                'message' => 'Invalid student import cron token.',
            ], 403);
        }

        $defaultBatchSize = (int) AppSetting::value('imports.cron_batch_size', config('siwes.imports.cron_batch_size', 1000));
        $batchSize = max(500, min((int) $request->integer('limit', $defaultBatchSize), 2000));
        $result = $studentImportService->processQueued($batchSize);

        return response()->json(array_merge([
            'message' => 'Queued student imports processed.',
            'batch_size' => $batchSize,
        ], $result));
    }
}
