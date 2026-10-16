<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('supervisor_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('semester_id')->constrained()->restrictOnDelete();
            $table->foreignId('idea_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description');
            $table->string('category', 100)->nullable();
            $table->json('keywords');
            // A project goes straight from in_progress to defended
            $table->enum('status', [
                'pending_approval', 'changes_requested', 'rejected', 'approved',
                'in_progress', 'defended', 'archived',
            ])->default('pending_approval');
            $table->unsignedTinyInteger('progress_percent')->default(0);
            $table->string('github_url')->nullable();
            $table->decimal('similarity_score', 5, 2)->nullable();
            $table->text('review_note')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('submitted_at');
            $table->timestamps();

            $table->index(['supervisor_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
