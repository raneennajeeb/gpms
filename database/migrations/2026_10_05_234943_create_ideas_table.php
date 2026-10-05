<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ideas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supervisor_id')->constrained('users')->restrictOnDelete();
            $table->string('title');
            $table->text('description');
            $table->json('technologies');
            $table->enum('status', ['available', 'reserved'])->default('available');
            $table->foreignId('reserved_by_team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ideas');
    }
};
