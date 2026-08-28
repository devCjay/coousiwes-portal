<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('student_imports', 'auto_activate_students')) {
            return;
        }

        Schema::table('student_imports', function (Blueprint $table): void {
            $table->boolean('auto_activate_students')->default(true)->after('failed_rows');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('student_imports', 'auto_activate_students')) {
            return;
        }

        Schema::table('student_imports', function (Blueprint $table): void {
            $table->dropColumn('auto_activate_students');
        });
    }
};
