<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interview_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('interview_schedule_id')->constrained()->restrictOnDelete();
            $table->text('summary');
            $table->text('strengths')->nullable();
            $table->text('weaknesses')->nullable();
            $table->string('recommendation', 32);
            $table->unsignedTinyInteger('rating');
            $table->foreignId('submitted_by_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('submitted_at');
            $table->timestamps();

            $table->unique(
                ['interview_schedule_id', 'submitted_by_id'],
                'feedback_interview_submitter_unique',
            );
            $table->index(['submitted_by_id', 'submitted_at'], 'feedback_submitter_date_idx');
            $table->index(['recommendation', 'submitted_at'], 'feedback_recommendation_date_idx');
            $table->index('submitted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interview_feedback');
    }
};
