<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('first_name', 100);
            $table->string('last_name', 100)->nullable();
            $table->string('email')->unique();
            $table->string('phone', 50)->nullable();
            $table->string('location')->nullable();
            $table->string('source', 100)->nullable();
            $table->decimal('experience_years', 4, 1)->nullable();
            $table->text('skills')->nullable();
            $table->string('current_position', 180)->nullable();
            $table->decimal('expected_salary', 15, 2)->nullable();
            $table->string('availability', 100)->nullable();
            $table->string('status', 32)->default('new');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'created_at']);
            $table->index('source');
            $table->index('availability');
            $table->index(['last_name', 'first_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidates');
    }
};
