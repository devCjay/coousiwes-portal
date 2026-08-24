<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('students')
            ->join('users', 'users.id', '=', 'students.user_id')
            ->when(Schema::hasColumn('students', 'deleted_at'), fn ($query) => $query->whereNull('students.deleted_at'))
            ->select('users.id', 'students.matric_no')
            ->orderBy('users.id')
            ->chunk(100, function ($records): void {
                foreach ($records as $record) {
                    DB::table('users')
                        ->where('id', $record->id)
                        ->update(['password' => Hash::make((string) $record->matric_no)]);
                }
            });
    }

    public function down(): void
    {
        //
    }
};
