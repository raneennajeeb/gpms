<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The committee picked for this one session, out of the branch pool
        Schema::create('session_committee', function (Blueprint $table) {
            $table->id();
            $table->foreignId('defense_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->enum('role', ['chair', 'member'])->default('member');
            $table->timestamps();

            // The same doctor cannot be added to one committee twice
            $table->unique(['defense_session_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_committee');
    }
};
