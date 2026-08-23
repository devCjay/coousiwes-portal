<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_placements', function (Blueprint $table): void {
            $table->unique('ticket_id', 'student_placements_ticket_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('student_placements', function (Blueprint $table): void {
            $table->dropUnique('student_placements_ticket_id_unique');
        });
    }
};
