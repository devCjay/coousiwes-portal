<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('email')->nullable()->change();
        });

        Schema::table('students', function (Blueprint $table): void {
            $table->unsignedBigInteger('faculty_id')->nullable()->change();
            $table->unsignedBigInteger('department_id')->nullable()->change();
            $table->unsignedBigInteger('academic_level_id')->nullable()->change();
            $table->unsignedBigInteger('academic_session_id')->nullable()->change();
        });

        $placeholderUserIds = DB::table('users')
            ->where('email', 'like', '%@students.coousiwes.local')
            ->pluck('id');

        if ($placeholderUserIds->isNotEmpty()) {
            DB::table('students')
                ->whereIn('user_id', $placeholderUserIds)
                ->whereNull('metadata')
                ->whereNull('gender')
                ->whereNull('date_of_birth')
                ->whereNull('address')
                ->whereNotExists(function ($query): void {
                    $query->selectRaw('1')
                        ->from('student_placements')
                        ->whereColumn('student_placements.student_id', 'students.id');
                })
                ->update([
                    'faculty_id' => null,
                    'department_id' => null,
                    'course_id' => null,
                    'academic_level_id' => null,
                    'academic_session_id' => null,
                ]);

            DB::table('users')
                ->whereIn('id', $placeholderUserIds)
                ->update(['email' => null]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('email')->nullable(false)->change();
        });

        Schema::table('students', function (Blueprint $table): void {
            $table->unsignedBigInteger('faculty_id')->nullable(false)->change();
            $table->unsignedBigInteger('department_id')->nullable(false)->change();
            $table->unsignedBigInteger('academic_level_id')->nullable(false)->change();
            $table->unsignedBigInteger('academic_session_id')->nullable(false)->change();
        });
    }
};
