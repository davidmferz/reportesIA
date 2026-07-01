<?php

namespace App\Services;

use App\Models\AITrainingExample;
use Illuminate\Support\Collection;

/**
 * Juez determinístico de similitud contra las salidas de entrenamiento.
 *
 * No usa IA: comparar estilo con otra IA sería caro, lento y probabilístico. Acá el
 * objetivo es tener una compuerta estable en el flujo de generación: el contenido
 * generado debe parecerse a AL MENOS una salida real usada en el entrenamiento.
 */
class OutputSimilarityJudgeService
{
    private const MIN_OVERALL_SCORE = 0.42;

    /** @var array<string, true> */
    private array $stopwords = [
        'ademas' => true, 'ahora' => true, 'ante' => true, 'aquel' => true, 'aquella' => true,
        'cada' => true, 'como' => true, 'con' => true, 'contra' => true, 'cual' => true,
        'cuando' => true, 'debe' => true, 'deben' => true, 'desde' => true, 'donde' => true,
        'durante' => true, 'este' => true, 'esta' => true, 'estos' => true, 'estas' => true,
        'para' => true, 'pero' => true, 'porque' => true, 'sobre' => true, 'solo' => true,
        'tambien' => true, 'tras' => true, 'una' => true, 'uno' => true, 'unos' => true,
        'unas' => true, 'del' => true, 'las' => true, 'los' => true, 'por' => true,
        'que' => true, 'sin' => true, 'sus' => true, 'the' => true, 'and' => true,
    ];

    /**
     * @param iterable<int, AITrainingExample|array<string, mixed>> $referenceOutputs
     * @return array<string, mixed>
     */
    public function judge(string $generatedContent, iterable $referenceOutputs): array
    {
        $references = $this->normalizeReferences($referenceOutputs);

        if (empty($references)) {
            return [
                'valid' => true,
                'status' => 'skipped_no_references',
                'threshold' => self::MIN_OVERALL_SCORE,
                'best_score' => null,
                'best_reference' => null,
                'comparisons' => [],
                'violations' => [],
                'feedback_for_ai' => null,
            ];
        }

        $comparisons = [];
        foreach ($references as $reference) {
            $score = $this->score($generatedContent, $reference['output_content']);
            $comparisons[] = [
                'id' => $reference['id'],
                'grupo_id' => $reference['grupo_id'],
                'capitulo' => $reference['capitulo'],
                'overall_score' => $score['overall_score'],
                'keyword_score' => $score['keyword_score'],
                'heading_score' => $score['heading_score'],
                'structure_score' => $score['structure_score'],
                'phrase_score' => $score['phrase_score'],
                'reference_profile' => $this->referenceProfile($reference['output_content']),
            ];
        }

        usort($comparisons, fn(array $a, array $b) => $b['overall_score'] <=> $a['overall_score']);
        $best = $comparisons[0];
        $valid = $best['overall_score'] >= self::MIN_OVERALL_SCORE;

        $violations = [];
        if (!$valid) {
            $percent = (int) round($best['overall_score'] * 100);
            $thresholdPercent = (int) round(self::MIN_OVERALL_SCORE * 100);
            $chapter = $best['capitulo'] ?: 'sin capítulo';
            $violations[] = [
                'type' => 'training_output_similarity_below_threshold',
                'severity' => 'critical',
                'detail' => "La salida generada solo alcanza {$percent}% de similitud contra la mejor salida de entrenamiento ({$chapter}); mínimo requerido: {$thresholdPercent}%.",
                'evidence' => "Mejor referencia: grupo {$best['grupo_id']} / {$chapter}",
            ];
        }

        return [
            'valid' => $valid,
            'status' => $valid ? 'passed' : 'failed',
            'threshold' => self::MIN_OVERALL_SCORE,
            'best_score' => $best['overall_score'],
            'best_reference' => [
                'id' => $best['id'],
                'grupo_id' => $best['grupo_id'],
                'capitulo' => $best['capitulo'],
                'profile' => $best['reference_profile'] ?? null,
            ],
            // Guardamos solo top 5 para auditoría sin inflar el JSON.
            'comparisons' => array_slice($comparisons, 0, 5),
            'violations' => $violations,
            'feedback_for_ai' => $valid ? null : $this->buildFeedbackForAI($best),
        ];
    }

    /**
     * @param iterable<int, AITrainingExample|array<string, mixed>> $referenceOutputs
     * @return array<int, array{id:int|string|null, grupo_id:int|string|null, capitulo:string|null, output_content:string}>
     */
    private function normalizeReferences(iterable $referenceOutputs): array
    {
        if ($referenceOutputs instanceof Collection) {
            $referenceOutputs = $referenceOutputs->all();
        }

        $references = [];
        foreach ($referenceOutputs as $reference) {
            $output = $this->referenceValue($reference, 'output_content');
            if (!is_string($output) || trim($output) === '') {
                continue;
            }

            $references[] = [
                'id' => $this->referenceValue($reference, 'id'),
                'grupo_id' => $this->referenceValue($reference, 'grupo_id'),
                'capitulo' => $this->referenceValue($reference, 'capitulo'),
                'output_content' => $output,
            ];
        }

        return $references;
    }

    private function referenceValue(mixed $reference, string $key): mixed
    {
        if (is_array($reference)) {
            return $reference[$key] ?? null;
        }

        if (is_object($reference)) {
            return $reference->{$key} ?? null;
        }

        return null;
    }

    /** @return array<string, float> */
    private function score(string $generated, string $reference): array
    {
        $generatedTokens = $this->tokens($generated);
        $referenceTokens = $this->tokens($reference);

        $keywordScore = $this->cosineSimilarity($generatedTokens, $referenceTokens);
        $headingScore = $this->headingSimilarity($generated, $reference);
        $structureScore = $this->structureSimilarity($generated, $reference);
        $phraseScore = $this->shingleSimilarity($generatedTokens, $referenceTokens, 3);

        $overall = ($keywordScore * 0.65)
            + ($headingScore * 0.20)
            + ($structureScore * 0.10)
            + ($phraseScore * 0.05);

        return [
            'overall_score' => round($overall, 4),
            'keyword_score' => round($keywordScore, 4),
            'heading_score' => round($headingScore, 4),
            'structure_score' => round($structureScore, 4),
            'phrase_score' => round($phraseScore, 4),
        ];
    }

    /** @return array{headings: array<int, string>, word_count: int, paragraph_count: int, has_table: bool, has_list: bool} */
    private function referenceProfile(string $reference): array
    {
        $features = $this->structureFeatures($reference);

        return [
            'headings' => array_slice($this->headings($reference), 0, 8),
            'word_count' => $features['word_count'],
            'paragraph_count' => $features['paragraph_count'],
            'has_table' => $features['has_table'],
            'has_list' => $features['list_ratio'] > 0,
        ];
    }

    /** @return array<int, string> */
    private function tokens(string $content): array
    {
        $normalized = $this->normalizeText($content);
        preg_match_all('/[\p{L}\p{N}]{3,}/u', $normalized, $matches);

        return array_values(array_filter($matches[0] ?? [], function (string $token): bool {
            return mb_strlen($token, 'UTF-8') >= 4 && !isset($this->stopwords[$token]);
        }));
    }

    private function normalizeText(string $content): string
    {
        $text = strip_tags($content);
        $text = mb_strtolower($text, 'UTF-8');
        $text = strtr($text, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
            'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u', 'Ü' => 'u', 'Ñ' => 'n',
        ]);
        $text = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $text) ?? $text;

        return preg_replace('/\s+/u', ' ', trim($text)) ?? trim($text);
    }

    /** @param array<int, string> $left @param array<int, string> $right */
    private function cosineSimilarity(array $left, array $right): float
    {
        if (empty($left) || empty($right)) {
            return 0.0;
        }

        $leftTf = array_count_values($left);
        $rightTf = array_count_values($right);
        $vocabulary = array_unique(array_merge(array_keys($leftTf), array_keys($rightTf)));

        $dot = 0.0;
        $leftNorm = 0.0;
        $rightNorm = 0.0;
        foreach ($vocabulary as $token) {
            $l = $leftTf[$token] ?? 0;
            $r = $rightTf[$token] ?? 0;
            $dot += $l * $r;
            $leftNorm += $l ** 2;
            $rightNorm += $r ** 2;
        }

        if ($leftNorm == 0.0 || $rightNorm == 0.0) {
            return 0.0;
        }

        return min(1.0, $dot / (sqrt($leftNorm) * sqrt($rightNorm)));
    }

    private function headingSimilarity(string $generated, string $reference): float
    {
        $left = $this->headings($generated);
        $right = $this->headings($reference);

        if (empty($left) && empty($right)) {
            return 1.0;
        }
        if (empty($left) || empty($right)) {
            return 0.0;
        }

        return $this->jaccard($left, $right);
    }

    /** @return array<int, string> */
    private function headings(string $content): array
    {
        $headings = [];
        $lines = preg_split('/\R/u', $content) ?: [];

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                continue;
            }

            $isMarkdownHeading = preg_match('/^#{1,6}\s+(.+)$/u', $trimmed, $matches) === 1;
            $candidate = $isMarkdownHeading ? $matches[1] : $trimmed;
            $withoutDecorators = trim($candidate, " \t\n\r\0\x0B*_:.-");

            $lettersOnly = preg_replace('/[^\p{L}]+/u', '', $withoutDecorators) ?? '';
            $upperOnly = mb_strtoupper($lettersOnly, 'UTF-8') === $lettersOnly && mb_strlen($lettersOnly, 'UTF-8') >= 5;
            $shortHeading = mb_strlen($withoutDecorators, 'UTF-8') <= 90
                && preg_match('/^[\p{Lu}\p{N}\s:.,()\-ÁÉÍÓÚÜÑ]+$/u', $withoutDecorators) === 1;

            if ($isMarkdownHeading || $upperOnly || $shortHeading) {
                $normalized = $this->normalizeText($withoutDecorators);
                if ($normalized !== '') {
                    $headings[] = $normalized;
                }
            }
        }

        return array_values(array_unique($headings));
    }

    private function structureSimilarity(string $generated, string $reference): float
    {
        $left = $this->structureFeatures($generated);
        $right = $this->structureFeatures($reference);

        $scores = [
            $this->ratioScore($left['word_count'], $right['word_count']),
            $this->ratioScore($left['paragraph_count'], $right['paragraph_count']),
            $this->ratioScore($left['heading_count'], $right['heading_count']),
            1.0 - min(1.0, abs($left['list_ratio'] - $right['list_ratio'])),
            $left['has_table'] === $right['has_table'] ? 1.0 : 0.5,
        ];

        return array_sum($scores) / count($scores);
    }

    /** @return array{word_count:int, paragraph_count:int, heading_count:int, list_ratio:float, has_table:bool} */
    private function structureFeatures(string $content): array
    {
        $plain = trim(strip_tags($content));
        $lines = preg_split('/\R/u', $plain) ?: [];
        $nonEmptyLines = array_values(array_filter($lines, fn(string $line): bool => trim($line) !== ''));
        $listLines = array_filter($nonEmptyLines, fn(string $line): bool => preg_match('/^\s*(?:[-*•]|\d+[.)])\s+/u', $line) === 1);

        return [
            'word_count' => count($this->tokens($plain)),
            'paragraph_count' => max(1, count(preg_split('/\R\s*\R/u', $plain) ?: [])),
            'heading_count' => count($this->headings($plain)),
            'list_ratio' => count($nonEmptyLines) > 0 ? count($listLines) / count($nonEmptyLines) : 0.0,
            'has_table' => preg_match('/\|.+\|/u', $plain) === 1,
        ];
    }

    private function ratioScore(int $left, int $right): float
    {
        if ($left === 0 && $right === 0) {
            return 1.0;
        }
        if ($left === 0 || $right === 0) {
            return 0.0;
        }

        return min($left, $right) / max($left, $right);
    }

    /** @param array<int, string> $left @param array<int, string> $right */
    private function shingleSimilarity(array $left, array $right, int $size): float
    {
        $leftShingles = $this->shingles($left, $size);
        $rightShingles = $this->shingles($right, $size);

        if (empty($leftShingles) && empty($rightShingles)) {
            return 1.0;
        }

        return $this->jaccard($leftShingles, $rightShingles);
    }

    /** @param array<int, string> $tokens @return array<int, string> */
    private function shingles(array $tokens, int $size): array
    {
        if (count($tokens) < $size) {
            return [];
        }

        $shingles = [];
        for ($i = 0; $i <= count($tokens) - $size; $i++) {
            $shingles[] = implode(' ', array_slice($tokens, $i, $size));
        }

        return array_values(array_unique($shingles));
    }

    /** @param array<int, string> $left @param array<int, string> $right */
    private function jaccard(array $left, array $right): float
    {
        $left = array_values(array_unique($left));
        $right = array_values(array_unique($right));
        $union = array_values(array_unique(array_merge($left, $right)));

        if (empty($union)) {
            return 0.0;
        }

        return count(array_intersect($left, $right)) / count($union);
    }

    /** @param array<string, mixed> $best */
    private function buildFeedbackForAI(array $best): string
    {
        $chapter = $best['capitulo'] ?: 'la salida de entrenamiento más cercana';
        $score = (int) round($best['overall_score'] * 100);
        $threshold = (int) round(self::MIN_OVERALL_SCORE * 100);
        $profile = $best['reference_profile'] ?? [];
        $profileLines = [];

        if (!empty($profile['headings'])) {
            $profileLines[] = 'Encabezados de referencia: ' . implode(' · ', $profile['headings']);
        }

        if (!empty($profile['word_count'])) {
            $profileLines[] = "Longitud de referencia aproximada: {$profile['word_count']} palabras en "
                . ($profile['paragraph_count'] ?? 'varios') . " párrafo(s).";
        }

        $formatHints = [];
        if (!empty($profile['has_table'])) {
            $formatHints[] = 'incluye tablas';
        }
        if (!empty($profile['has_list'])) {
            $formatHints[] = 'incluye listas';
        }
        if (!empty($formatHints)) {
            $profileLines[] = 'Formato observado: ' . implode(', ', $formatHints) . '.';
        }

        $profileBlock = !empty($profileLines)
            ? "\n\nReferencia estructural de la mejor salida (NO copies datos específicos del ejemplo):\n- "
                . implode("\n- ", $profileLines)
            : '';

        return "La salida anterior NO se parece lo suficiente a los archivos de SALIDA del entrenamiento. "
            . "La mejor coincidencia fue '{$chapter}' con {$score}% de similitud; mínimo requerido: {$threshold}%.\n"
            . "Reescribí el documento para acercarlo a las salidas de entrenamiento: estructura, encabezados, "
            . "profundidad, vocabulario técnico, longitud relativa y forma de cierre. Mantené los datos reales de la entrada; "
            . "no copies datos específicos de los ejemplos.{$profileBlock}\n\nEntregá SOLO el documento corregido.";
    }
}
