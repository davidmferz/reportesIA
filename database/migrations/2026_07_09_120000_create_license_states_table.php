<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('license_states', function (Blueprint $table) {
            $table->id();
            $table->text('raw_token');
            $table->json('payload');
            $table->timestamp('valid_from');
            $table->timestamp('valid_until');
            $table->unsignedInteger('max_users');
            $table->timestamp('last_check_at')->nullable();
            $table->string('last_check_result')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->foreignId('activated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('license_states');
    }
};
