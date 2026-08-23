<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SiwesHealthCheckCommand extends Command
{
    protected $signature = 'siwes:health-check {--json : Emit machine-readable JSON}';

    protected $description = 'Run operational readiness checks for database, queues, storage, cache, and required portal configuration.';

    public function handle(): int
    {
        $checks = [
            'database' => $this->databaseCheck(),
            'migrations' => $this->tableCheck(),
            'storage' => $this->storageCheck(),
            'queue' => $this->queueCheck(),
            'cache' => $this->cacheCheck(),
            'security' => $this->securityCheck(),
        ];

        $failed = collect($checks)->filter(fn (array $check): bool => $check['status'] !== 'ok');

        if ($this->option('json')) {
            $this->line((string) json_encode([
                'status' => $failed->isEmpty() ? 'ok' : 'failed',
                'checks' => $checks,
            ], JSON_PRETTY_PRINT));

            return $failed->isEmpty() ? self::SUCCESS : self::FAILURE;
        }

        foreach ($checks as $name => $check) {
            $method = $check['status'] === 'ok' ? 'info' : 'error';
            $this->{$method}(strtoupper($check['status'])." {$name}: {$check['message']}");
        }

        return $failed->isEmpty() ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @return array{status: string, message: string}
     */
    private function databaseCheck(): array
    {
        try {
            DB::connection()->getPdo();

            return ['status' => 'ok', 'message' => 'Database connection is available.'];
        } catch (\Throwable $exception) {
            return ['status' => 'failed', 'message' => $exception->getMessage()];
        }
    }

    /**
     * @return array{status: string, message: string}
     */
    private function tableCheck(): array
    {
        $requiredTables = ['users', 'students', 'supervisors', 'tickets', 'payments', 'notifications', 'audit_logs'];
        $missing = collect($requiredTables)->reject(fn (string $table): bool => Schema::hasTable($table));

        return $missing->isEmpty()
            ? ['status' => 'ok', 'message' => 'Required tables are present.']
            : ['status' => 'failed', 'message' => 'Missing tables: '.$missing->join(', ')];
    }

    /**
     * @return array{status: string, message: string}
     */
    private function storageCheck(): array
    {
        $paths = [storage_path('app'), storage_path('framework'), storage_path('logs')];
        $blocked = collect($paths)->reject(fn (string $path): bool => is_dir($path) && is_writable($path));

        return $blocked->isEmpty()
            ? ['status' => 'ok', 'message' => 'Storage directories are writable.']
            : ['status' => 'failed', 'message' => 'Unwritable paths: '.$blocked->join(', ')];
    }

    /**
     * @return array{status: string, message: string}
     */
    private function queueCheck(): array
    {
        $failedJobs = Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0;

        return $failedJobs === 0
            ? ['status' => 'ok', 'message' => 'No failed jobs are recorded.']
            : ['status' => 'failed', 'message' => "{$failedJobs} failed jobs require review."];
    }

    /**
     * @return array{status: string, message: string}
     */
    private function cacheCheck(): array
    {
        try {
            cache()->put('siwes-health-check', now()->toIso8601String(), 60);

            return cache()->has('siwes-health-check')
                ? ['status' => 'ok', 'message' => 'Cache store accepts writes.']
                : ['status' => 'failed', 'message' => 'Cache write was not persisted.'];
        } catch (\Throwable $exception) {
            return ['status' => 'failed', 'message' => $exception->getMessage()];
        }
    }

    /**
     * @return array{status: string, message: string}
     */
    private function securityCheck(): array
    {
        if (! config('siwes.security.headers_enabled')) {
            return ['status' => 'failed', 'message' => 'Security headers are disabled.'];
        }

        if (! config('session.encrypt')) {
            return ['status' => 'failed', 'message' => 'Session encryption is disabled.'];
        }

        return ['status' => 'ok', 'message' => 'Security headers and encrypted sessions are enabled.'];
    }
}
