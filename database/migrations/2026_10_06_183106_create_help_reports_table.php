<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('help_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ticket_number', 20)->unique();
            $table->enum('type', ['technical_error', 'page_not_working', 'suggestion', 'other']);
            $table->text('description');
            $table->string('screenshot_path')->nullable();
            $table->enum('status', ['new', 'in_progress', 'closed'])->default('new');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('help_reports');
    }
};