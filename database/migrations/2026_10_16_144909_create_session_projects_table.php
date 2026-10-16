<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The order of the projects inside a session, and the slot each one gets
        Schema::create('session_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('defense_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();

            // A project is defended once; no two projects share a slot in one session
            $table->unique(['defense_session_id', 'position']);
            $table->unique(['defense_session_id', 'start_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_projects');
    }
};
