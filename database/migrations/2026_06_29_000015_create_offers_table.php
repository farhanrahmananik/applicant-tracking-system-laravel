<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->restrictOnDelete();
            $table->string('offer_title');
            $table->decimal('salary_amount', 15, 2);
            $table->string('currency', 3)->default('EUR');
            $table->string('employment_type', 32);
            $table->date('expected_joining_date')->nullable();
            $table->date('expiry_date');
            $table->string('status', 32)->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(
                ['application_id', 'status', 'deleted_at'],
                'offer_application_status_deleted_idx',
            );
            $table->index(['status', 'expiry_date'], 'offer_status_expiry_idx');
            $table->index('expected_joining_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};
