<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('students', 'course_id')) {
            return;
        }

        if (DB::getDriverName() === 'sqlite') {
            Schema::table('students', function (Blueprint $table): void {
                $table->foreignId('course_id')->nullable()->change();
            });

            return;
        }

        Schema::table('students', function (Blueprint $table): void {
            $table->unsignedBigInteger('course_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('students', 'course_id')) {
            return;
        }

        Schema::table('students', function (Blueprint $table): void {
            $table->unsignedBigInteger('course_id')->nullable(false)->change();
        });
    }
};
