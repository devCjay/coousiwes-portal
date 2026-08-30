<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('student_imports', 'mark_workshop_fee_paid')) {
            return;
        }

        Schema::table('student_imports', function (Blueprint $table): void {
            $table->boolean('mark_workshop_fee_paid')->default(false)->after('auto_activate_students');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('student_imports', 'mark_workshop_fee_paid')) {
            return;
        }

        Schema::table('student_imports', function (Blueprint $table): void {
            $table->dropColumn('mark_workshop_fee_paid');
        });
    }
};
