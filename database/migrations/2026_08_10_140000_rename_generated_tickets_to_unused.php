<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('tickets')
            ->where('status', 'generated')
            ->update(['status' => 'unused']);
    }

    public function down(): void
    {
        DB::table('tickets')
            ->where('status', 'unused')
            ->whereNull('student_id')
            ->update(['status' => 'generated']);
    }
};
