<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Files and screenshots exchanged between a student and a supervisor
        // inside a project. The files themselves live in storage/app/private.
        Schema::create('project_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            // The table is named explicitly: Laravel would guess "comments" from comment_id
            $table->foreignId('comment_id')->nullable()->constrained('project_comments')->nullOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('file_path');
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->unsignedInteger('size'); // In bytes
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_attachments');
    }
};
