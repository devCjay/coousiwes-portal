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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('ticket_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider', 30)->default('korapay');
            $table->string('reference')->unique();
            $table->unsignedInteger('amount');
            $table->string('currency', 3)->default('NGN');
            $table->string('status', 30)->default('pending');
            $table->string('checkout_url')->nullable();
            $table->string('provider_status')->nullable();
            $table->string('webhook_event')->nullable();
            $table->string('webhook_event_id')->nullable()->unique();
            $table->json('payload')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'status']);
            $table->index(['provider', 'status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
