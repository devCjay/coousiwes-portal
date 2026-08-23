<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('students', 'student_no')) {
            return;
        }

        if (DB::getDriverName() === 'sqlite') {
            DB::statement('DROP INDEX IF EXISTS students_student_no_unique');
        } else {
            Schema::table('students', function (Blueprint $table): void {
                $table->dropUnique('students_student_no_unique');
            });
        }

        Schema::table('students', function (Blueprint $table): void {
            $table->dropColumn('student_no');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('students', 'student_no')) {
            return;
        }

        Schema::table('students', function (Blueprint $table): void {
            $table->string('student_no', 40)->nullable()->unique()->after('user_id');
        });
    }
};
