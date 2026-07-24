<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Estructura sugerida de documento (hoja "Estructuras_proyecto" del Excel).
 *
 * Cada combinación Tipo de servicio + Tipo de documento define los apartados del
 * documento y su configuración sugerida. La configuración vive como columnas del
 * tipo de documento porque hay exactamente una por combinación.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_document_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalog_document_type_id')->constrained('catalog_document_types')->cascadeOnDelete();
            $table->unsignedInteger('orden');
            $table->string('apartado');
            $table->text('contenido');
            $table->timestamps();

            $table->unique(['catalog_document_type_id', 'orden']);
        });

        Schema::table('catalog_document_types', function (Blueprint $table) {
            // Texto y no booleano: el Excel usa "Sí" / "No" / "Opcional",
            // y los indicadores son rangos ("2 a 4").
            $table->string('indicadores_sugeridos')->nullable()->after('nombre');
            $table->string('requiere_tablas')->nullable()->after('indicadores_sugeridos');
            $table->string('requiere_formatos')->nullable()->after('requiere_tablas');
            $table->string('requiere_diagrama')->nullable()->after('requiere_formatos');
            $table->string('clasificacion_documental')->nullable()->after('requiere_diagrama');
        });
    }

    public function down(): void
    {
        Schema::table('catalog_document_types', function (Blueprint $table) {
            $table->dropColumn([
                'indicadores_sugeridos',
                'requiere_tablas',
                'requiere_formatos',
                'requiere_diagrama',
                'clasificacion_documental',
            ]);
        });

        Schema::dropIfExists('catalog_document_sections');
    }
};
