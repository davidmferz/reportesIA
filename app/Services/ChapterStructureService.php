<?php

namespace App\Services;

use App\Models\Chapter;
use App\Models\ReportType;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Traduce la estructura sugerida del catálogo a capítulos del tipo de reporte.
 *
 * El puente entre el Excel y la generación: la combinación Tipo de servicio +
 * Tipo de documento determina los apartados, y los capítulos ya son la unidad
 * con la que la IA genera. Esto NO toca el prompt ni el entrenamiento.
 */
class ChapterStructureService
{
    /**
     * Apartados que se crearían, sin escribir nada.
     *
     * @return list<array{orden: int, apartado: string, contenido: string}>
     */
    public function previewFor(ReportType $reportType): array
    {
        return $this->sectionsFor($reportType)
            ->map(fn ($section) => [
                'orden' => $section->orden,
                'apartado' => $section->apartado,
                'contenido' => $section->contenido,
            ])
            ->all();
    }

    /**
     * Crea los capítulos del tipo de reporte a partir del catálogo.
     *
     * Nunca pisa capítulos existentes salvo que se pida explícitamente; el
     * reemplazo usa soft delete, así que lo anterior sigue siendo recuperable.
     *
     * @return int Capítulos creados.
     */
    public function applyTo(ReportType $reportType, bool $replace = false): int
    {
        $sections = $this->sectionsFor($reportType);

        $existentes = $reportType->chapters()->count();

        if ($existentes > 0 && ! $replace) {
            throw new RuntimeException(
                "El tipo de reporte ya tiene {$existentes} capítulo(s). Confirmá el reemplazo para sustituirlos."
            );
        }

        return DB::transaction(function () use ($reportType, $sections, $replace) {
            if ($replace) {
                $reportType->chapters()->each(fn (Chapter $chapter) => $chapter->delete());
            }

            foreach ($sections as $section) {
                Chapter::create([
                    'report_type_id' => $reportType->id,
                    'nombre' => $section->apartado,
                    'descripcion' => $section->contenido,
                    'orden' => $section->orden,
                    'created_by' => Auth::id(),
                ]);
            }

            return $sections->count();
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\Catalog\DocumentSection>
     */
    private function sectionsFor(ReportType $reportType)
    {
        $documentType = $reportType->catalogDocumentType;

        if (! $documentType) {
            throw new RuntimeException(
                'El tipo de reporte no tiene un tipo de documento del catálogo seleccionado.'
            );
        }

        $sections = $documentType->sections()->get();

        if ($sections->isEmpty()) {
            throw new RuntimeException(
                "El tipo de documento «{$documentType->nombre}» no tiene estructura cargada en el catálogo."
            );
        }

        return $sections;
    }
}
