<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_generations', function (Blueprint $table) {
            $table->boolean('validation_passed')->default(true)->after('total_tokens');
            $table->json('validation_result')->nullable()->after('validation_passed');
            $table->unsignedTinyInteger('validation_attempts')->default(1)->after('validation_result');
            $table->boolean('sanitized_post_hoc')->default(false)->after('validation_attempts');
        });
    }

    public function down(): void
    {
        Schema::table('ai_generations', function (Blueprint $table) {
            $table->dropColumn(['validation_passed', 'validation_result', 'validation_attempts', 'sanitized_post_hoc']);
        });
    }
};
