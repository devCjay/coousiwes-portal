<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tickets') || ! Schema::hasColumn('tickets', 'student_id')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE tickets MODIFY student_id BIGINT UNSIGNED NULL');
        } elseif (DB::getDriverName() === 'sqlite') {
            if (Schema::hasTable('tickets_legacy_nullable_student')) {
                Schema::drop('tickets_legacy_nullable_student');
                $this->ensureSqliteIndexes();

                return;
            }

            if ($this->sqliteStudentIdIsNullable()) {
                $this->ensureSqliteIndexes();

                return;
            }

            $this->rebuildSqliteTicketsTable(nullableStudent: true);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('tickets') || ! Schema::hasColumn('tickets', 'student_id')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE tickets MODIFY student_id BIGINT UNSIGNED NOT NULL');
        } elseif (DB::getDriverName() === 'sqlite') {
            $this->rebuildSqliteTicketsTable(nullableStudent: false);
        }
    }

    private function rebuildSqliteTicketsTable(bool $nullableStudent): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');
        DB::statement('DROP INDEX IF EXISTS tickets_code_hash_unique');
        DB::statement('DROP INDEX IF EXISTS tickets_student_id_status_index');
        DB::statement('DROP INDEX IF EXISTS tickets_status_expires_at_index');
        DB::statement('ALTER TABLE tickets RENAME TO tickets_legacy_nullable_student');

        $studentColumn = $nullableStudent
            ? 'student_id integer'
            : 'student_id integer not null';

        DB::statement(<<<SQL
            CREATE TABLE tickets (
                id integer primary key autoincrement not null,
                {$studentColumn},
                generated_by integer,
                code_hash varchar not null,
                amount integer not null,
                currency varchar not null default 'NGN',
                status varchar not null default 'unused',
                assigned_at datetime,
                paid_at datetime,
                used_at datetime,
                expires_at datetime,
                metadata text,
                created_at datetime,
                updated_at datetime,
                deleted_at datetime,
                foreign key(student_id) references students(id) on delete set null,
                foreign key(generated_by) references users(id) on delete set null
            )
        SQL);

        DB::statement(<<<SQL
            INSERT INTO tickets (
                id, student_id, generated_by, code_hash, amount, currency, status,
                assigned_at, paid_at, used_at, expires_at, metadata, created_at, updated_at, deleted_at
            )
            SELECT
                id, student_id, generated_by, code_hash, amount, currency, status,
                assigned_at, paid_at, used_at, expires_at, metadata, created_at, updated_at, deleted_at
            FROM tickets_legacy_nullable_student
        SQL);

        $this->ensureSqliteIndexes();
        DB::statement('DROP TABLE tickets_legacy_nullable_student');
        DB::statement('PRAGMA foreign_keys = ON');
    }

    private function ensureSqliteIndexes(): void
    {
        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS tickets_code_hash_unique ON tickets (code_hash)');
        DB::statement('CREATE INDEX IF NOT EXISTS tickets_student_id_status_index ON tickets (student_id, status)');
        DB::statement('CREATE INDEX IF NOT EXISTS tickets_status_expires_at_index ON tickets (status, expires_at)');
    }

    private function sqliteStudentIdIsNullable(): bool
    {
        $columns = DB::select('PRAGMA table_info(tickets)');

        foreach ($columns as $column) {
            if (($column->name ?? null) === 'student_id') {
                return (int) ($column->notnull ?? 0) === 0;
            }
        }

        return false;
    }
};
