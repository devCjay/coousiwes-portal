<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('students', 'deleted_at')) {
            return;
        }

        Schema::table('students', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('students', 'deleted_at')) {
            return;
        }

        Schema::table('students', function (Blueprint $table): void {
            $table->softDeletes();
        });
    }
};
