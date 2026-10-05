<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_stage_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->enum('type', ['report', 'source_code', 'presentation', 'other']);
            $table->dateTime('due_date');
            $table->enum('status', ['pending', 'submitted', 'late', 'needs_revision', 'accepted'])->default('pending');
            $table->text('supervisor_notes')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
