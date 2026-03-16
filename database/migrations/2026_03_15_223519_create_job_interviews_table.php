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
        Schema::create('job_interviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_application_id')
                  ->constrained()
                  ->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('interview_type', [
                'phone',
                'technical',
                'behavioral',
                'final',
                'other'
            ])->default('phone');
            $table->datetime('scheduled_at');
            $table->string('interviewer_name')->nullable();
            $table->text('notes')->nullable();
            $table->text('ai_prep')->nullable();      // AI-generated prep guide
            $table->text('ai_feedback')->nullable();  // AI feedback after interview
            $table->enum('outcome', [
                'pending',
                'passed',
                'failed',
                'cancelled'
            ])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_interviews');
    }
};
