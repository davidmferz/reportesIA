<?php

namespace App\Services;

use App\Models\AITraining;
use App\Models\AITrainingExample;
use App\Models\ReportType;

/**
 * Audita ejemplos de entrenamiento contra el prompt del cliente.
 * Identifica ejemplos contaminados con palabras prohibidas, secciones no
 * permitidas, y violaciones de longitud, para evitar que arrastren al modelo
 * hacia patrones que el cliente rechaza.
 */
class ExampleAuditorService
{
    public function __construct(
        protected PromptParserService $parser,
    ) {}

    /**
     * Audita todos los ejemplos de un tipo de reporte.
     * Retorna un reporte con cada ejemplo marcado como ok/warning/contaminated.
     */
    public function auditReportType(ReportType $reportType): array
    {
        $training = $reportType->training;
        if (!$training) {
            return [
                'report_type' => $reportType->nombre,
                'status' => 'no_training',
                'message' => 'Este tipo de reporte no tiene entrenamiento',
                'examples' => [],
                'summary' => $this->emptySummary(),
            ];
        }

        $forbidden = $this->parser->extractForbiddenTerms($reportType->prompt);
        $limits = $this->parser->extractWordLimits($reportType->prompt);

        $results = [];
        foreach ($training->examples as $example) {
            $results[] = $this->auditExample($example, $forbidden, $limits);
        }

        return [
            'report_type' => $reportType->nombre,
            'report_type_id' => $reportType->id,
            'status' => 'audited',
            'forbidden_terms_detected' => $forbidden,
            'word_limits' => $limits,
            'examples' => $results,
            'summary' => $this->summarize($results),
        ];
    }

    public function auditExample(AITrainingExample $example, array $forbidden, array $limits): array
    {
        $output = (string) $example->output_content;
        $outputLower = mb_strtolower($output, 'UTF-8');

        $hits = [];
        foreach ($forbidden as $term) {
            if ($term !== '' && mb_strpos($outputLower, $term) !== false) {
                $hits[] = $term;
            }
        }

        $wordCount = str_word_count(strip_tags($output));
        $wordViolation = null;
        if ($limits['max'] !== null && $wordCount > $limits['max']) {
            $wordViolation = "excede {$limits['max']} palabras (tiene {$wordCount})";
        } elseif ($limits['min'] !== null && $wordCount < $limits['min']) {
            $wordViolation = "queda corto de {$limits['min']} palabras (tiene {$wordCount})";
        }

        $status = match (true) {
            count($hits) >= 3 || $wordViolation !== null => 'contaminated',
            count($hits) >= 1 => 'warning',
            default => 'ok',
        };

        return [
            'example_id' => $example->id,
            'capitulo' => $example->capitulo,
            'status' => $status,
            'forbidden_hits' => $hits,
            'word_count' => $wordCount,
            'word_violation' => $wordViolation,
            'output_preview' => mb_substr($output, 0, 200, 'UTF-8'),
        ];
    }

    private function summarize(array $results): array
    {
        $summary = $this->emptySummary();
        $summary['total'] = count($results);
        foreach ($results as $r) {
            $summary[$r['status']]++;
        }
        return $summary;
    }

    private function emptySummary(): array
    {
        return ['total' => 0, 'ok' => 0, 'warning' => 0, 'contaminated' => 0];
    }
}
