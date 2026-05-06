<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forbidden_words', function (Blueprint $table) {
            $table->id();
            $table->string('word', 100)->unique();
            $table->string('reason', 255)->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->index('active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forbidden_words');
    }
};
