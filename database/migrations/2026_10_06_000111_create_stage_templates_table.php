<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stage_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en');
            $table->text('description_ar');
            $table->unsignedTinyInteger('sort_order');
            $table->unsignedTinyInteger('weight');
            $table->unsignedSmallInteger('days_after_approval');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stage_templates');
    }
};