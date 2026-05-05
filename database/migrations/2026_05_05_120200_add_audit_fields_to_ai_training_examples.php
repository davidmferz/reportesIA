<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_training_examples', function (Blueprint $table) {
            $table->string('audit_status', 20)->default('ok')->after('processed_at');
            $table->json('audit_findings')->nullable()->after('audit_status');
            $table->timestamp('audited_at')->nullable()->after('audit_findings');
            $table->boolean('excluido_few_shot')->default(false)->after('audited_at');
        });
    }

    public function down(): void
    {
        Schema::table('ai_training_examples', function (Blueprint $table) {
            $table->dropColumn(['audit_status', 'audit_findings', 'audited_at', 'excluido_few_shot']);
        });
    }
};
