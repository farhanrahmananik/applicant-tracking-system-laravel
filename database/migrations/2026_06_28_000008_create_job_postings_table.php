<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_postings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->string('employment_type', 32);
            $table->string('workplace_type', 32);
            $table->string('location')->nullable();
            $table->unsignedInteger('openings')->default(1);
            $table->decimal('salary_min', 15, 2)->nullable();
            $table->decimal('salary_max', 15, 2)->nullable();
            $table->string('currency', 3)->nullable()->default('EUR');
            $table->string('experience_level', 100)->nullable();
            $table->longText('description');
            $table->longText('requirements')->nullable();
            $table->longText('responsibilities')->nullable();
            $table->longText('benefits')->nullable();
            $table->string('status', 32)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->date('closes_at')->nullable();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'slug']);
            $table->index(['company_id', 'status']);
            $table->index('department_id');
            $table->index('status');
            $table->index('published_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_postings');
    }
};
