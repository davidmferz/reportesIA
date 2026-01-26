<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ai_generations', function (Blueprint $table) {
            $table->foreignId('chapter_id')->nullable()->after('user_id')->constrained('chapters')->onDelete('set null');
            $table->index('chapter_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_generations', function (Blueprint $table) {
            $table->dropForeign(['chapter_id']);
            $table->dropIndex(['chapter_id']);
            $table->dropColumn('chapter_id');
        });
    }
};
