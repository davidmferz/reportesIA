<?php

namespace App\Services;

/**
 * Valida la salida generada por la IA contra las reglas del prompt del cliente.
 * Trabaja en modo determinístico: NO depende de la IA para validar, usa parseo
 * y matching directo sobre el texto. Esto cierra la brecha probabilística del LLM.
 */
class OutputValidatorService
{
    public function __construct(
        protected PromptParserService $parser,
    ) {}

    /**
     * Valida una salida generada contra el prompt del cliente.
     *
     * @param  array<string, string>  $expectations  Requisitos de formato que el catálogo declara
     *                                               para el entregable elegido (requiere_tablas,
     *                                               requiere_formatos, requiere_diagrama).
     * @return array{
     *   valid: bool,
     *   violations: array<int, array{type: string, severity: string, detail: string, evidence?: string}>,
     *   metrics: array{word_count: int, char_count: int, forbidden_hits: array<string, int>},
     *   feedback_for_ai: string|null
     * }
     */
    public function validate(string $output, ?string $customPrompt, array $expectations = []): array
    {
        $violations = [];
        // Combinamos las palabras prohibidas extraídas del prompt del cliente con la
        // lista global del módulo admin. Las globales aplican siempre, sin importar
        // qué prompt se use, así un editor puede prohibir términos a nivel sistema.
        $forbiddenTerms = array_values(array_unique(array_merge(
            $this->parser->extractForbiddenTerms($customPrompt),
            \App\Models\ForbiddenWord::activeWords()
        )));
        $limits = $this->parser->extractWordLimits($customPrompt);

        $cleanOutput = strip_tags($output);
        $wordCount = str_word_count($cleanOutput, 0, 'áéíóúüñÁÉÍÓÚÜÑ');
        $charCount = mb_strlen($cleanOutput, 'UTF-8');

        // 1. Palabras prohibidas
        $hits = $this->findForbiddenHits($output, $forbiddenTerms);
        foreach ($hits as $term => $count) {
            $violations[] = [
                'type' => 'forbidden_word',
                'severity' => 'critical',
                'detail' => "Palabra prohibida \"{$term}\" aparece {$count} vez/veces",
                'evidence' => $this->extractContext($output, $term),
            ];
        }

        // 2. Límite de palabras (máx)
        if ($limits['max'] !== null && $wordCount > $limits['max']) {
            $exceso = $wordCount - $limits['max'];
            $violations[] = [
                'type' => 'word_limit_exceeded',
                'severity' => 'critical',
                'detail' => "Excede el máximo de {$limits['max']} palabras por {$exceso} (total: {$wordCount})",
            ];
        }

        // 3. Límite de palabras (mín)
        // Severidad CRITICAL para mantener simetría con word_limit_exceeded y disparar
        // reintento con feedback. El feedback en buildFeedbackForAI ya advierte explícitamente
        // "NO rellenes con conocimiento externo: solo desarrollá lo que está en la entrada",
        // así que escalar a critical no fomenta invención — solo fuerza al modelo a expandir
        // sobre la entrada provista.
        if ($limits['min'] !== null && $wordCount < $limits['min']) {
            $faltan = $limits['min'] - $wordCount;
            $violations[] = [
                'type' => 'word_limit_below',
                'severity' => 'critical',
                'detail' => "Por debajo del mínimo de {$limits['min']} palabras (faltan {$faltan})",
            ];
        }

        // 4. Secciones no solicitadas (heurística: detecta secciones genéricas comunes
        //    que NO estén explícitamente mencionadas en el prompt del cliente)
        $unsolicitedSections = $this->detectUnsolicitedSections($output, $customPrompt);
        foreach ($unsolicitedSections as $section) {
            $violations[] = [
                'type' => 'unsolicited_section',
                'severity' => 'warning',
                'detail' => "Sección \"{$section}\" no fue solicitada en el prompt",
            ];
        }

        // 5. Frases de relleno típicas que el cliente del PDF rechaza explícitamente
        $fillerPhrases = $this->detectFillerPhrases($output);
        foreach ($fillerPhrases as $phrase) {
            $violations[] = [
                'type' => 'filler_phrase',
                'severity' => 'warning',
                'detail' => "Frase de justificación detectada: \"{$phrase}\"",
            ];
        }

        // 6. Requisitos de formato del catálogo para el entregable elegido.
        //    SIEMPRE warning: un critical dispara reintento con feedback, y forzar una
        //    tabla que el CASO DE REFERENCIA no tiene contradice al Prompt Maestro
        //    ("No cambies la estructura", "No añadas apartados nuevos"). El Excel orienta,
        //    el caso de referencia manda.
        foreach ($this->detectMissingExpectations($output, $expectations) as $missing) {
            $violations[] = $missing;
        }

        $valid = empty(array_filter($violations, fn($v) => $v['severity'] === 'critical'));

        return [
            'valid' => $valid,
            'violations' => $violations,
            'metrics' => [
                'word_count' => $wordCount,
                'char_count' => $charCount,
                'forbidden_hits' => $hits,
            ],
            'feedback_for_ai' => $this->buildFeedbackForAI($violations, $limits, $wordCount),
        ];
    }

    /**
     * Contrasta la salida contra lo que el catálogo declara obligatorio para el
     * entregable. Solo actúa sobre "Sí": "No" y "Opcional" nunca generan aviso.
     *
     * La detección es determinística (regex sobre el texto), coherente con el resto
     * del validador: no se le pregunta a la IA si cumplió.
     *
     * `indicadores_sugeridos` queda deliberadamente fuera: "2 a 4" no se puede contar
     * sin interpretar el texto, y una heurística ahí generaría ruido. Esa señal viaja
     * por el prompt (encuadre), no por la validación.
     *
     * @param  array<string, string>  $expectations
     * @return array<int, array{type: string, severity: string, detail: string}>
     */
    private function detectMissingExpectations(string $output, array $expectations): array
    {
        $reglas = [
            'requiere_tablas' => [
                'type' => 'missing_expected_table',
                'detail' => 'El catálogo indica que este entregable requiere tablas y la salida no incluye ninguna tabla Markdown.',
                'presente' => fn (string $texto) => $this->hasMarkdownTable($texto),
            ],
            'requiere_diagrama' => [
                'type' => 'missing_expected_diagram',
                'detail' => 'El catálogo indica que este entregable requiere diagrama y la salida no menciona ninguno.',
                // No podemos producir imágenes (lo prohíbe el mensaje de formato), así que
                // lo verificable es que la salida represente el diagrama de algún modo.
                'presente' => fn (string $texto) => (bool) preg_match(
                    '/\b(diagramas?|esquemas?|flujogramas?|organigramas?|figuras?)\b/iu',
                    $texto
                ),
            ],
            'requiere_formatos' => [
                'type' => 'missing_expected_format',
                'detail' => 'El catálogo indica que este entregable requiere formatos y la salida no incluye ninguno.',
                'presente' => fn (string $texto) => (bool) preg_match(
                    '/\b(formatos?|formularios?|plantillas?|anexos?)\b/iu',
                    $texto
                ),
            ],
        ];

        $violations = [];

        foreach ($reglas as $columna => $regla) {
            $exigido = $expectations[$columna] ?? null;

            if ($exigido === null || mb_strtolower(trim($exigido), 'UTF-8') !== 'sí') {
                continue;
            }

            if (($regla['presente'])($output)) {
                continue;
            }

            $violations[] = [
                'type' => $regla['type'],
                'severity' => 'warning',
                'detail' => $regla['detail'],
            ];
        }

        return $violations;
    }

    /**
     * Una tabla Markdown válida necesita fila de encabezado y fila separadora.
     * Buscar solo pipes daría falsos positivos con cualquier "a | b" en prosa.
     */
    private function hasMarkdownTable(string $output): bool
    {
        return (bool) preg_match('/^[ \t]*\|.+\|[ \t]*\R[ \t]*\|[\s:|-]+\|[ \t]*$/mu', $output);
    }

    /**
     * Cuenta apariciones de cada palabra prohibida en el output (case-insensitive).
     */
    private function findForbiddenHits(string $output, array $forbiddenTerms): array
    {
        $hits = [];
        $lower = mb_strtolower($output, 'UTF-8');
        foreach ($forbiddenTerms as $term) {
            if ($term === '') continue;
            // \b no funciona bien con UTF-8 — uso lookaround manual
            $pattern = '/(?<![a-záéíóúüñ])' . preg_quote($term, '/') . '(?![a-záéíóúüñ])/u';
            $count = preg_match_all($pattern, $lower);
            if ($count > 0) {
                $hits[$term] = $count;
            }
        }
        return $hits;
    }

    private function extractContext(string $output, string $term): string
    {
        $lower = mb_strtolower($output, 'UTF-8');
        $pos = mb_strpos($lower, $term, 0, 'UTF-8');
        if ($pos === false) return '';
        $start = max(0, $pos - 30);
        $len = mb_strlen($term, 'UTF-8') + 60;
        return '…' . trim(mb_substr($output, $start, $len, 'UTF-8')) . '…';
    }

    /**
     * Detecta secciones "genéricas profesionales" que el modelo agrega sin pedirlas.
     * Solo las marca si NO aparecen en el prompt del cliente.
     */
    private function detectUnsolicitedSections(string $output, ?string $customPrompt): array
    {
        $genericSections = [
            'Resumen Ejecutivo', 'Resumen ejecutivo',
            'Conclusiones', 'Conclusión',
            'Recomendaciones',
            'Antecedentes',
            'Justificación',
            'Marco Teórico', 'Marco teórico',
        ];

        $promptLower = $customPrompt ? mb_strtolower($customPrompt, 'UTF-8') : '';
        $detected = [];

        foreach ($genericSections as $section) {
            $sectionLower = mb_strtolower($section, 'UTF-8');
            // Solo cuenta como violación si: (a) aparece como heading en el output
            // y (b) NO está mencionada en el prompt del cliente
            $headingPattern = '/^(?:#+\s*|[\*_]{0,2})' . preg_quote($section, '/') . '[\s:\*_]*$/mui';
            if (preg_match($headingPattern, $output)) {
                if (!str_contains($promptLower, $sectionLower)) {
                    $detected[] = $section;
                }
            }
        }

        return array_unique($detected);
    }

    /**
     * Frases de relleno/justificación que el cliente del PDF rechazó explícitamente.
     */
    private function detectFillerPhrases(string $output): array
    {
        $patterns = [
            '/\bse espera que\b/ui' => 'se espera que',
            '/\bpermitir[áa]\s+\w+/ui' => 'permitirá …',
            '/\bcontribuy[ae]\s+\w+/ui' => 'contribuye/contribuya …',
            '/\bes\s+(?:fundamental|esencial|clave)\s+(?:para|que)\b/ui' => 'es fundamental/esencial/clave para …',
            '/\bcon\s+el\s+fin\s+de\b/ui' => 'con el fin de',
            '/\bcon\s+el\s+objetivo\s+de\s+\w+/ui' => 'con el objetivo de …',
        ];

        $detected = [];
        foreach ($patterns as $regex => $label) {
            if (preg_match($regex, $output)) {
                $detected[] = $label;
            }
        }
        return $detected;
    }

    /**
     * Construye un mensaje de feedback que se pasa a la IA en el reintento.
     * Ese mensaje es lo que diferencia "validar" de "auto-corregir".
     */
    private function buildFeedbackForAI(array $violations, array $limits, int $wordCount): ?string
    {
        if (empty($violations)) return null;

        $lines = ["La salida anterior viola las siguientes reglas. CORREGÍ y reentregá:"];

        $forbidden = array_filter($violations, fn($v) => $v['type'] === 'forbidden_word');
        if (!empty($forbidden)) {
            $words = array_map(fn($v) => str_replace(['Palabra prohibida "', '"'], '', explode(' aparece', $v['detail'])[0]), $forbidden);
            $lines[] = '- Eliminá COMPLETAMENTE estas palabras prohibidas: ' . implode(', ', $words);
        }

        foreach ($violations as $v) {
            if ($v['type'] === 'word_limit_exceeded') {
                // Los LLMs cuentan tokens, no palabras, y "sé más conciso" rara vez los lleva
                // a un número específico. Damos un objetivo concreto 10% por debajo del máximo
                // para que tengan margen y aún así no excedan después de su propio "ajuste".
                if ($limits['max'] !== null) {
                    $target = max(50, (int) floor($limits['max'] * 0.9));
                    $lines[] = "- Generaste {$wordCount} palabras y el LÍMITE DURO es {$limits['max']}. APUNTÁ a {$target} palabras (10% por debajo del máximo, te deja margen). Cortá información secundaria, NO la esencial. Contá las palabras al terminar y verificá que no superes {$limits['max']}.";
                } else {
                    $lines[] = "- {$v['detail']}. Reescribí más conciso, sin perder información esencial.";
                }
            }
            if ($v['type'] === 'word_limit_below') {
                $lines[] = "- {$v['detail']}. NO rellenes con conocimiento externo: solo desarrollá lo que está en la entrada.";
            }
            if ($v['type'] === 'unsolicited_section') {
                $lines[] = "- {$v['detail']}. Eliminá esa sección.";
            }
            if ($v['type'] === 'filler_phrase') {
                $lines[] = "- {$v['detail']}. Reescribí en lenguaje técnico directo, sin justificaciones ni proyecciones.";
            }
        }

        $lines[] = '';
        $lines[] = 'Reentregá SOLO el documento corregido, sin disculpas ni explicaciones.';

        return implode("\n", $lines);
    }
}
