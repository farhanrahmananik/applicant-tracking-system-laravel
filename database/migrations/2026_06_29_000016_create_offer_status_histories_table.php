<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offer_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 32);
            $table->string('to_status', 32);
            $table->string('note', 500)->nullable();
            $table->foreignId('changed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('changed_at');
            $table->timestamp('created_at')->nullable();

            $table->index(
                ['offer_id', 'changed_at', 'id'],
                'offer_history_offer_date_idx',
            );
            $table->index(['to_status', 'changed_at'], 'offer_history_status_date_idx');
            $table->index(['changed_by_id', 'changed_at'], 'offer_history_actor_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offer_status_histories');
    }
};
