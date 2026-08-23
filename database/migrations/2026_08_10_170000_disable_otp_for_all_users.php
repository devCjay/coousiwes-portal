<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->update(['otp_enabled' => false]);
    }

    public function down(): void
    {
        //
    }
};
