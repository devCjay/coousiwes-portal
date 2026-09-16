<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('supervisors', function (Blueprint $table): void {
            $table->foreignId('faculty_id')->nullable()->after('organization')->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->after('faculty_id')->constrained()->nullOnDelete();
            $table->string('rank', 80)->nullable()->after('department');

            $table->index(['department_id', 'rank']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supervisors', function (Blueprint $table): void {
            $table->dropIndex(['department_id', 'rank']);
            $table->dropConstrainedForeignId('department_id');
            $table->dropConstrainedForeignId('faculty_id');
            $table->dropColumn('rank');
        });
    }
};
