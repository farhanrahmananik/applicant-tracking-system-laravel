<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_resumes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('original_name');
            $table->string('stored_path', 1024);
            $table->string('disk', 64)->default('local');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size_bytes');
            $table->string('extension', 10);
            $table->boolean('is_primary')->default(false);
            $table->timestamp('uploaded_at');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['candidate_id', 'is_primary', 'uploaded_at']);
            $table->index(['uploaded_by_id', 'uploaded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_resumes');
    }
};
