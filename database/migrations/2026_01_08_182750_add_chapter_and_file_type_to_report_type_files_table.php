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
        Schema::table('report_type_files', function (Blueprint $table) {
            $table->string('capitulo', 255)->nullable()->after('report_type_id');
            $table->enum('tipo_archivo', ['entrada', 'salida'])->nullable()->after('capitulo');
            $table->string('grupo_id', 36)->nullable()->after('tipo_archivo');

            // Indexes for faster queries
            $table->index('grupo_id');
            $table->index('tipo_archivo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('report_type_files', function (Blueprint $table) {
            $table->dropIndex(['grupo_id']);
            $table->dropIndex(['tipo_archivo']);
            $table->dropColumn(['capitulo', 'tipo_archivo', 'grupo_id']);
        });
    }
};
