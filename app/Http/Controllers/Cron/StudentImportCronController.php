<?php

namespace App\Http\Controllers\Cron;

use App\Http\Controllers\Controller;
use App\Services\StudentImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentImportCronController extends Controller
{
    public function __invoke(Request $request, StudentImportService $studentImportService): JsonResponse
    {
        $configuredToken = (string) config('siwes.imports.cron_token');
        $requestToken = (string) $request->query('token', '');

        abort_if($configuredToken === '' || ! hash_equals($configuredToken, $requestToken), 403);

        $batchSize = max(500, min((int) $request->integer('limit', (int) config('siwes.imports.cron_batch_size', 1000)), 2000));
        $result = $studentImportService->processQueued($batchSize);

        return response()->json(array_merge([
            'message' => 'Queued student imports processed.',
            'batch_size' => $batchSize,
        ], $result));
    }
}
