<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SiwesBackupSqliteCommand extends Command
{
    protected $signature = 'siwes:backup-sqlite {--path= : Destination directory for the backup file}';

    protected $description = 'Create a timestamped SQLite database backup for local and small-deployment recovery tests.';

    public function handle(): int
    {
        if (config('database.default') !== 'sqlite') {
            $this->error('The SQLite backup command only supports DB_CONNECTION=sqlite.');

            return self::FAILURE;
        }

        $source = (string) config('database.connections.sqlite.database');

        if (! is_file($source)) {
            $this->error("SQLite database file was not found at {$source}.");

            return self::FAILURE;
        }

        $targetDirectory = (string) ($this->option('path') ?: storage_path('app/backups'));
        File::ensureDirectoryExists($targetDirectory);

        $target = $targetDirectory.'/coou-siwes-'.now()->format('Ymd-His').'.sqlite';

        if (! copy($source, $target)) {
            $this->error("Could not copy SQLite database to {$target}.");

            return self::FAILURE;
        }

        $this->info("SQLite backup created: {$target}");

        return self::SUCCESS;
    }
}
