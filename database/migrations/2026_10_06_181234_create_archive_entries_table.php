<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('archive_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faculty_id')->constrained()->restrictOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            // Filled only when a live GPMS project gets archived
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();

            $table->string('title');
            $table->text('description');
            $table->unsignedSmallInteger('year');
            $table->string('supervisor_name');
            $table->date('defense_date');
            $table->string('hall_name', 100);
            $table->enum('grade', ['excellent', 'very_good', 'good']);
            $table->decimal('final_score', 5, 2)->nullable();
            $table->boolean('competition')->default(false);
            $table->string('competition_name')->nullable();
            $table->string('competition_result')->nullable();
            $table->string('github_url')->nullable();
            $table->string('cover_image')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->timestamps();

            $table->index(['faculty_id', 'year', 'grade', 'competition']);
            $table->fullText(['title', 'description']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('archive_entries');
    }
};