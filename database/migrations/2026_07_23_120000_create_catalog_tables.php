<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogo de clasificación de proyectos (Generador_Proyectos_Anidado_Excel_2019.xlsx).
 *
 * Dos árboles independientes:
 *   Sector > Rama > Subrama > Especialidad
 *   Tipo de servicio > Tipo de documento
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_sectors', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->timestamps();
        });

        Schema::create('catalog_branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalog_sector_id')->constrained('catalog_sectors')->cascadeOnDelete();
            $table->string('nombre');
            $table->timestamps();

            $table->unique(['catalog_sector_id', 'nombre']);
        });

        Schema::create('catalog_subbranches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalog_branch_id')->constrained('catalog_branches')->cascadeOnDelete();
            $table->string('nombre');
            $table->timestamps();

            $table->unique(['catalog_branch_id', 'nombre']);
        });

        Schema::create('catalog_specialties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalog_subbranch_id')->constrained('catalog_subbranches')->cascadeOnDelete();
            $table->string('nombre');
            $table->timestamps();

            $table->unique(['catalog_subbranch_id', 'nombre']);
        });

        Schema::create('catalog_service_types', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->timestamps();
        });

        Schema::create('catalog_document_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalog_service_type_id')->constrained('catalog_service_types')->cascadeOnDelete();
            $table->string('nombre');
            $table->timestamps();

            $table->unique(['catalog_service_type_id', 'nombre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_document_types');
        Schema::dropIfExists('catalog_service_types');
        Schema::dropIfExists('catalog_specialties');
        Schema::dropIfExists('catalog_subbranches');
        Schema::dropIfExists('catalog_branches');
        Schema::dropIfExists('catalog_sectors');
    }
};
