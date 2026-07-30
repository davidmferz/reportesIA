<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ata el ejemplo de entrenamiento a un capítulo real.
 *
 * Hasta ahora `report_type_files.capitulo` era un string libre que NADIE llenaba:
 * el controlador pasaba null fijo. Se reemplaza por una FK a `chapters`, que es
 * la misma tabla contra la que se elige capítulo al generar — sin eso no hay
 * forma de emparejar el ejemplo con la generación.
 *
 * La columna vieja `capitulo` NO se borra: sobrevive como etiqueta legible
 * (el nombre del capítulo) y la usan el few-shot y el juez de similitud. Pasa a
 * ser nullable porque 'Sin capítulo' era un centinela truthy que terminaba
 * impreso dentro del prompt.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('report_type_files', function (Blueprint $table) {
            $table->foreignId('chapter_id')
                ->nullable()
                ->after('report_type_id')
                ->constrained('chapters')
                // Si se borra el capítulo, el ejemplo sigue siendo válido: queda
                // sin clasificar, no se pierde el archivo de entrenamiento.
                ->nullOnDelete();
        });

        Schema::table('ai_training_examples', function (Blueprint $table) {
            $table->foreignId('chapter_id')
                ->nullable()
                ->after('grupo_id')
                ->constrained('chapters')
                ->nullOnDelete();

            $table->string('capitulo')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('ai_training_examples', function (Blueprint $table) {
            $table->dropConstrainedForeignId('chapter_id');
        });

        Schema::table('report_type_files', function (Blueprint $table) {
            $table->dropConstrainedForeignId('chapter_id');
        });

        // `capitulo` se deja nullable a propósito: revertirlo a NOT NULL rompería
        // las filas que legítimamente quedaron sin capítulo.
    }
};
