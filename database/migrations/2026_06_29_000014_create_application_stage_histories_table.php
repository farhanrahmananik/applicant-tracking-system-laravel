<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_stage_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->string('from_stage', 32);
            $table->string('to_stage', 32);
            $table->foreignId('changed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note', 500)->nullable();
            $table->timestamp('changed_at');
            $table->timestamp('created_at')->nullable();

            $table->index(
                ['application_id', 'changed_at', 'id'],
                'stage_history_application_date_idx',
            );
            $table->index(['to_stage', 'changed_at'], 'stage_history_stage_date_idx');
            $table->index(['changed_by_id', 'changed_at'], 'stage_history_actor_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_stage_histories');
    }
};
