<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('archive_committee', function (Blueprint $table) {
            $table->id();
            $table->foreignId('archive_entry_id')->constrained()->cascadeOnDelete();
            $table->string('member_name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('archive_committee');
    }
};
