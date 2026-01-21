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
        Schema::create('ai_training_examples', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_training_id')->constrained('ai_trainings')->onDelete('cascade');
            $table->uuid('grupo_id')->index();
            $table->string('capitulo');
            $table->longText('input_content');
            $table->longText('output_content');
            $table->integer('input_files_count')->default(0);
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['ai_training_id', 'grupo_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_training_examples');
    }
};
