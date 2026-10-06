<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('defense_schedule_id')->constrained()->restrictOnDelete();
            $table->foreignId('project_id')->constrained()->restrictOnDelete();
            $table->foreignId('evaluator_id')->constrained('users')->restrictOnDelete();

            // Each score is 0-100; the 0-100 range is validated in the form request
            $table->decimal('report_score', 5, 2);
            $table->decimal('presentation_score', 5, 2);
            $table->decimal('discussion_score', 5, 2);
            $table->decimal('project_score', 5, 2);
            $table->decimal('final_score', 5, 2);

            $table->text('comments')->nullable();
            $table->boolean('is_locked')->default(false);
            $table->timestamps();

            $table->unique(['project_id', 'evaluator_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluations');
    }
};