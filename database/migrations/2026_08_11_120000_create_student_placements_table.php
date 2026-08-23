<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_placements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('ticket_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('academic_level_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('academic_session_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('siwes_year');
            $table->string('attachment_period', 40);
            $table->string('company_name')->nullable();
            $table->text('company_address')->nullable();
            $table->string('company_state', 120)->nullable();
            $table->string('company_lga', 120)->nullable();
            $table->string('company_supervisor_phone', 40)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['siwes_year', 'academic_session_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_placements');
    }
};
