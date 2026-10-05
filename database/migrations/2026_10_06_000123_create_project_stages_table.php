<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stage_template_id')->constrained()->restrictOnDelete();

            // Copied from the template so later template edits don't change running projects
            $table->string('name_ar');
            $table->string('name_en');
            $table->text('description_ar');
            $table->unsignedTinyInteger('sort_order');
            $table->unsignedTinyInteger('weight');

            $table->enum('status', ['locked', 'in_progress', 'completed'])->default('locked');
            $table->date('due_date')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_stages');
    }
};