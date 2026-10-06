<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('archive_technologies', function (Blueprint $table) {
            $table->foreignId('archive_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('technology_id')->constrained()->cascadeOnDelete();

            $table->primary(['archive_entry_id', 'technology_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('archive_technologies');
    }
};