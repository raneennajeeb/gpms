<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submission_versions', function (Blueprint $table) {
            $table->id();
            // restrict: uploaded versions are a permanent record and must never be deleted
            $table->foreignId('submission_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('version_number');
            $table->string('file_path');
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size');
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['submission_id', 'version_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submission_versions');
    }
};
