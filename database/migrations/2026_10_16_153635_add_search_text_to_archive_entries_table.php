<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('archive_entries', function (Blueprint $table) {
            // Title, summary, description, student names and technologies, all
            // folded by App\Support\ArabicText so searching ignores spelling variants
            $table->text('search_text')->nullable()->after('idea_summary');
            $table->fullText('search_text');
        });
    }

    public function down(): void
    {
        Schema::table('archive_entries', function (Blueprint $table) {
            $table->dropFullText(['search_text']);
            $table->dropColumn('search_text');
        });
    }
};
