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
        Schema::create('task_occurrences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->date('occurrence_date');
            $table->dateTime('due_at');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('priority', 32);
            $table->string('status', 32)->default('pending');
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->text('employee_note')->nullable();
            $table->dateTime('viewed_at')->nullable();
            $table->timestamps();

            $table->unique(['task_id', 'user_id', 'occurrence_date']);
            $table->index(['user_id', 'status', 'due_at']);
            $table->index(['task_id', 'occurrence_date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_occurrences');
    }
};
