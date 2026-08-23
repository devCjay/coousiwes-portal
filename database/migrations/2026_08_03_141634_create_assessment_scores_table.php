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
        Schema::create('assessment_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assessment_rubric_item_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('score');
            $table->unsignedSmallInteger('max_score');
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->unique(['assessment_id', 'assessment_rubric_item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assessment_scores');
    }
};
