<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained()->restrictOnDelete();
            $table->foreignId('job_posting_id')->constrained()->restrictOnDelete();
            $table->string('source', 100)->nullable();
            $table->date('applied_date');
            $table->string('current_status', 32)->default('applied');
            $table->text('notes')->nullable();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(
                ['candidate_id', 'job_posting_id', 'current_status', 'deleted_at'],
                'app_candidate_job_status_deleted_idx',
            );
            $table->index(
                ['job_posting_id', 'current_status', 'applied_date'],
                'app_job_status_date_idx',
            );
            $table->index(['current_status', 'applied_date'], 'app_status_date_idx');
            $table->index('source');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
