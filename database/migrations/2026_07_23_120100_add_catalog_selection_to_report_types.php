<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Clasificación del tipo de reporte según el catálogo anidado.
 * Toda la selección es opcional: hoy solo se persiste, no altera generación ni entrenamiento.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('report_types', function (Blueprint $table) {
            $table->foreignId('catalog_sector_id')->nullable()->after('nombre')->constrained('catalog_sectors')->nullOnDelete();
            $table->foreignId('catalog_branch_id')->nullable()->after('catalog_sector_id')->constrained('catalog_branches')->nullOnDelete();
            $table->foreignId('catalog_subbranch_id')->nullable()->after('catalog_branch_id')->constrained('catalog_subbranches')->nullOnDelete();
            $table->foreignId('catalog_specialty_id')->nullable()->after('catalog_subbranch_id')->constrained('catalog_specialties')->nullOnDelete();
            $table->foreignId('catalog_service_type_id')->nullable()->after('catalog_specialty_id')->constrained('catalog_service_types')->nullOnDelete();
            $table->foreignId('catalog_document_type_id')->nullable()->after('catalog_service_type_id')->constrained('catalog_document_types')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('report_types', function (Blueprint $table) {
            $table->dropConstrainedForeignId('catalog_document_type_id');
            $table->dropConstrainedForeignId('catalog_service_type_id');
            $table->dropConstrainedForeignId('catalog_specialty_id');
            $table->dropConstrainedForeignId('catalog_subbranch_id');
            $table->dropConstrainedForeignId('catalog_branch_id');
            $table->dropConstrainedForeignId('catalog_sector_id');
        });
    }
};
