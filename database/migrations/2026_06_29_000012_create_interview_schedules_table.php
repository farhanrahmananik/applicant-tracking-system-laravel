<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interview_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->restrictOnDelete();
            $table->foreignId('interviewer_id')->constrained('users')->restrictOnDelete();
            $table->string('type', 32);
            $table->string('status', 32)->default('scheduled');
            $table->dateTime('scheduled_at');
            $table->unsignedSmallInteger('duration_minutes');
            $table->string('location')->nullable();
            $table->string('meeting_link', 2048)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['application_id', 'scheduled_at'], 'interview_application_date_idx');
            $table->index(['interviewer_id', 'scheduled_at'], 'interview_interviewer_date_idx');
            $table->index(['status', 'scheduled_at'], 'interview_status_date_idx');
            $table->index(['type', 'scheduled_at'], 'interview_type_date_idx');
            $table->index('scheduled_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interview_schedules');
    }
};
