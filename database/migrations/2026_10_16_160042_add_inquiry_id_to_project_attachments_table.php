<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_attachments', function (Blueprint $table) {
            // Ties a screenshot or file to the inquiry it was sent with (step 04, batch B)
            $table->foreignId('inquiry_id')->nullable()->after('comment_id')
                ->constrained('supervisor_inquiries')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('project_attachments', function (Blueprint $table) {
            $table->dropForeign(['inquiry_id']);
            $table->dropColumn('inquiry_id');
        });
    }
};
