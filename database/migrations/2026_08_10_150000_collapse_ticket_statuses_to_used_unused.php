<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('tickets')
            ->whereIn('status', ['paid', 'used'])
            ->update(['status' => 'used']);

        DB::table('tickets')
            ->whereIn('status', ['generated', 'assigned', 'expired', 'revoked', 'unused'])
            ->update(['status' => 'unused']);
    }

    public function down(): void
    {
        DB::table('tickets')
            ->where('status', 'used')
            ->update(['status' => 'paid']);
    }
};
