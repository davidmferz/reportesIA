<?php

namespace App\Services;

use App\Models\ReportType;
use App\Models\ReportTypeFile;
use App\Models\AITraining;
use App\Models\AITrainingExample;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

class AITrainingService
{
    protected DocumentExtractorService $extractor;

    /**
     * Modelo por defecto de OpenAI a utilizar
     */
    protected string $defaultModel = 'gpt-5-mini';

    /**
     * Límites de tokens por modelo
     */
    protected array $modelLimits = [
        'gpt-5' => ['context' => 400000, 'output' => 128000],
        'gpt-5-mini' => ['context' => 400000, 'output' => 128000],
        'gpt-5-nano' => ['context' => 400000, 'output' => 128000],
        'gpt-4o-mini' => ['context' => 128000, 'output' => 16384],
        'gpt-4o' => ['context' => 128000, 'output' => 16384],
        'gpt-4-turbo' => ['context' => 128000, 'output' => 4096],
        'gpt-3.5-turbo' => ['context' => 16385, 'output' => 4096],
    ];

    protected OutputValidatorService $validator;

    /**
     * Máximo de reintentos del loop de auto-corrección.
     * Más de 2 da rendimientos decrecientes y consume tokens innecesarios.
     */
    protected int $maxValidationRetries = 2;

    public function __construct(DocumentExtractorService $extractor, OutputValidatorService $validator)
    {
        $this->extractor = $extractor;
        $this->validator = $validator;
    }

    /**
     * GPT-5 (y modelos de razonamiento o1/o3) cambiaron el nombre del parámetro:
     * 'max_tokens' fue reemplazado por 'max_completion_tokens'. GPT-4 y anteriores
     * todavía aceptan el viejo. Devolvemos el array con el nombre correcto según el modelo.
     */
    protected function tokenLimitParam(string $model, int $maxTokens): array
    {
        if (str_starts_with($model, 'gpt-5') || str_starts_with($model, 'o1') || str_starts_with($model, 'o3')) {
            return ['max_completion_tokens' => $maxTokens];
        }
        return ['max_tokens' => $maxTokens];
    }

    /**
     * Procesa todos los ejemplos de entrenamiento de un tipo de reporte
     * y crea/actualiza el entrenamiento de IA
     */
    public function processTraining(ReportType $reportType): AITraining
    {
        // Crear o actualizar el registro de entrenamiento
        $training = AITraining::updateOrCreate(
            ['report_type_id' => $reportType->id],
            [
                'status' => 'processing',
                'last_trained_at' => now(),
            ]
        );

        try {
            // Obtener todos los grupos de archivos del tipo de reporte
            $fileGroups = ReportTypeFile::where('report_type_id', $reportType->id)
                ->whereNotNull('grupo_id')
                ->get()
                ->groupBy('grupo_id');

            $examplesProcessed = 0;
            $allInputContents = [];
            $allOutputContents = [];

            foreach ($fileGroups as $grupoId => $files) {
                $archivosEntrada = $files->where('tipo_archivo', 'entrada');
                $archivoSalida = $files->where('tipo_archivo', 'salida')->first();

                if ($archivosEntrada->isEmpty() || !$archivoSalida) {
                    continue;
                }

                // Extraer contenido de archivos de entrada
                $inputContent = '';
                foreach ($archivosEntrada as $archivo) {
                    $inputContent .= "--- Archivo: {$archivo->nombre_original} ---\n";
                    $inputContent .= $this->extractor->extractText($archivo->ruta);
                    $inputContent .= "\n\n";
                }

                // Extraer contenido del archivo de salida
                $outputContent = $this->extractor->extractText($archivoSalida->ruta);

                // Guardar para análisis de patrones
                $allInputContents[] = $inputContent;
                $allOutputContents[] = $outputContent;

                // Crear o actualizar el ejemplo de entrenamiento
                AITrainingExample::updateOrCreate(
                    [
                        'ai_training_id' => $training->id,
                        'grupo_id' => $grupoId,
                    ],
                    [
                        'capitulo' => $archivosEntrada->first()->capitulo ?? 'Sin capítulo',
                        'input_content' => $inputContent,
                        'output_content' => $outputContent,
                        'input_files_count' => $archivosEntrada->count(),
                        'processed_at' => now(),
                    ]
                );

                $examplesProcessed++;
            }

            // Construir el prompt del sistema con análisis de patrones
            $systemPrompt = $this->buildEnhancedSystemPrompt($reportType, $allOutputContents);

            // Actualizar el entrenamiento con el prompt del sistema generado
            $training->update([
                'status' => 'ready',
                'system_prompt' => $systemPrompt,
                'examples_count' => $examplesProcessed,
                'error_message' => null,
            ]);

            Log::info("Entrenamiento completado para {$reportType->nombre}: {$examplesProcessed} ejemplos procesados");

            return $training;

        } catch (\Exception $e) {
            Log::error("Error procesando entrenamiento: " . $e->getMessage());

            $training->update([
                'status' => 'error',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Construye un prompt del sistema. Las instrucciones del cliente tienen MÁXIMA prioridad
     * y NUNCA pueden ser contradichas por reglas del sistema.
     */
    protected function buildEnhancedSystemPrompt(ReportType $reportType, array $outputContents): string
    {
        $customPrompt = $reportType->prompt ? trim($reportType->prompt) : '';
        $modoEstricto = (bool) ($reportType->modo_estricto ?? false);

        if ($modoEstricto && !empty($customPrompt)) {
            return $this->buildStrictSystemPrompt($reportType, $customPrompt);
        }

        return $this->buildStandardSystemPrompt($reportType, $customPrompt, $outputContents);
    }

    /**
     * Modo estricto: el prompt del cliente es la única ley. Sin patrones, sin reglas
     * adicionales que puedan entrar en conflicto.
     */
    protected function buildStrictSystemPrompt(ReportType $reportType, string $customPrompt): string
    {
        return <<<PROMPT
Generás documentos del tipo "{$reportType->nombre}".

## INSTRUCCIONES OBLIGATORIAS (PRIORIDAD MÁXIMA)
Las siguientes instrucciones son la ÚNICA ley para construir el documento. No las amplíes,
no las interpretes, no las contradigas con conocimiento general. Si una instrucción del
usuario entra en conflicto con cualquier otra consideración, GANA la instrucción del usuario.

{$customPrompt}

## CONTRATO DE EJECUCIÓN
- La fuente de verdad es EXCLUSIVAMENTE el contenido de entrada del usuario.
- No incorpores información, contexto, beneficios, conclusiones, ni suposiciones que no
  deriven literalmente de la entrada.
- No agregues secciones, encabezados ni cierres que no estén explícitamente solicitados.
- Si una sección no se puede desarrollar sin violar las reglas, mantenela implícita
  o omitila — NO rellenes.
- Antes de entregar, releé las instrucciones del usuario y eliminá cualquier contenido
  que las viole.
PROMPT;
    }

    /**
     * Modo estándar: usa ejemplos y patrones, pero el prompt del cliente sigue mandando
     * sobre las reglas del sistema.
     */
    protected function buildStandardSystemPrompt(ReportType $reportType, string $customPrompt, array $outputContents): string
    {
        $patternAnalysis = $this->analyzeOutputPatterns($outputContents, $customPrompt);

        $customPromptSection = '';
        if (!empty($customPrompt)) {
            $customPromptSection = <<<CUSTOM

## INSTRUCCIONES DEL USUARIO (PRIORIDAD MÁXIMA)
Estas instrucciones son la PRIMERA ley. Si alguna regla posterior o algún patrón de los
ejemplos las contradice, GANAN estas instrucciones. No las amplíes ni las interpretes.

{$customPrompt}

CUSTOM;
        }

        $prompt = <<<PROMPT
Generás documentos del tipo "{$reportType->nombre}" a partir de los archivos de entrada.
{$customPromptSection}
## ROL
Tu tarea es transformar la entrada en una salida coherente, manteniendo fidelidad estricta
a la información provista. Los ejemplos sirven como REFERENCIA de estilo, no como plantilla
obligatoria — si las instrucciones del usuario piden otra cosa, seguilas a ellas.

## PROCESO
1. Extraé los datos clave de la entrada (cifras, nombres, fechas, hechos).
2. Reorganizá esa información según las instrucciones del usuario.
3. Si no hay instrucciones específicas, usá los ejemplos como guía de estilo.
4. NO inventes datos que no estén en la entrada.

## PATRONES DE REFERENCIA (NO obligatorios si el usuario pide otra cosa)
{$patternAnalysis}

## REGLAS BASE
- No inventes información que no esté en los documentos de entrada.
- Si falta información necesaria, indicá qué falta en lugar de rellenar.
- Respetá la longitud y el tono solicitados por el usuario.
PROMPT;

        return $prompt;
    }

    /**
     * Analiza los patrones comunes en los contenidos de salida
     */
    protected function analyzeOutputPatterns(array $outputContents, ?string $customPrompt = null): string
    {
        if (empty($outputContents)) {
            return "No se detectaron patrones específicos.";
        }

        // Filtrar headings que contienen palabras prohibidas según el prompt del cliente.
        // Si los inyectamos como "secciones comunes", el modelo los reproduce aunque
        // el prompt diga "no agregar secciones".
        $forbiddenTerms = [];
        if ($customPrompt !== null) {
            $parser = new PromptParserService();
            $forbiddenTerms = $parser->extractForbiddenTerms($customPrompt);
        }

        $patterns = [];

        // Detectar encabezados comunes
        $headings = [];
        foreach ($outputContents as $content) {
            preg_match_all('/^#+\s*(.+)$/m', $content, $matches);
            if (!empty($matches[1])) {
                $headings = array_merge($headings, $matches[1]);
            }
            // También detectar encabezados en mayúsculas
            preg_match_all('/^([A-ZÁÉÍÓÚÑ\s]{5,})$/m', $content, $capsMatches);
            if (!empty($capsMatches[1])) {
                $headings = array_merge($headings, array_map('trim', $capsMatches[1]));
            }
        }

        // Filtrar headings contaminados con palabras prohibidas
        if (!empty($forbiddenTerms)) {
            $headings = array_filter($headings, function ($h) use ($forbiddenTerms) {
                $lower = mb_strtolower($h, 'UTF-8');
                foreach ($forbiddenTerms as $term) {
                    if ($term !== '' && mb_strpos($lower, $term) !== false) {
                        return false;
                    }
                }
                return true;
            });
        }

        // Contar frecuencia de encabezados
        $headingCounts = array_count_values($headings);
        arsort($headingCounts);
        $commonHeadings = array_slice(array_keys($headingCounts), 0, 10);

        if (!empty($commonHeadings)) {
            $patterns[] = "**Secciones comunes detectadas:**";
            foreach ($commonHeadings as $heading) {
                $patterns[] = "- " . trim($heading);
            }
        }

        // Detectar si hay tablas
        $hasTables = false;
        foreach ($outputContents as $content) {
            if (preg_match('/\|.*\|/', $content)) {
                $hasTables = true;
                break;
            }
        }
        if ($hasTables) {
            $patterns[] = "\n**Formato:** Los documentos incluyen tablas con formato Markdown";
        }

        // Detectar si hay listas
        $hasLists = false;
        foreach ($outputContents as $content) {
            if (preg_match('/^[\-\*\d+\.]\s+/m', $content)) {
                $hasLists = true;
                break;
            }
        }
        if ($hasLists) {
            $patterns[] = "**Formato:** Los documentos incluyen listas numeradas o con viñetas";
        }

        // Estimar longitud promedio
        $avgLength = array_sum(array_map('strlen', $outputContents)) / count($outputContents);
        $lengthDesc = $avgLength < 1000 ? 'corta' : ($avgLength < 5000 ? 'media' : 'extensa');
        $patterns[] = "\n**Longitud típica:** Los documentos de salida tienen una extensión {$lengthDesc} (aprox. " . number_format($avgLength) . " caracteres)";

        return implode("\n", $patterns);
    }

    /**
     * Genera una salida basándose en el entrenamiento y nuevos archivos de entrada
     */
    public function generateOutput(AITraining $training, array $inputFiles, ?string $model = null): array
    {
        if ($training->status !== 'ready') {
            throw new \Exception("El entrenamiento no está listo. Estado actual: {$training->status}");
        }

        $model = $model ?? $this->defaultModel;

        // Extraer contenido de los nuevos archivos de entrada
        $inputContent = '';
        foreach ($inputFiles as $file) {
            $inputContent .= "### Archivo: {$file['name']}\n";
            $inputContent .= "```\n";
            $inputContent .= $this->extractor->extractText($file['path']);
            $inputContent .= "\n```\n\n";
        }

        // Calcular tokens disponibles para ejemplos
        $modelLimits = $this->modelLimits[$model] ?? $this->modelLimits[$this->defaultModel];
        $maxContextTokens = $modelLimits['context'];
        $maxOutputTokens = min($modelLimits['output'], 8000); // Limitar salida

        // Estimar tokens del sistema y entrada (aproximado: 4 chars = 1 token)
        $systemTokens = (int)(strlen($training->system_prompt) / 4);
        $inputTokens = (int)(strlen($inputContent) / 4);
        $reserveForOutput = $maxOutputTokens;

        $availableForExamples = $maxContextTokens - $systemTokens - $inputTokens - $reserveForOutput - 1000; // Margen de seguridad

        // Construir los mensajes para la API de OpenAI
        $messages = [
            [
                'role' => 'system',
                'content' => $training->system_prompt,
            ],
        ];

        // En modo estricto, los few-shot ejemplos se excluyen: pesan más que las
        // instrucciones del prompt y arrastran a la IA hacia patrones aprendidos
        // (palabras prohibidas, secciones extra, justificaciones) que el cliente
        // explícitamente rechaza.
        $modoEstricto = (bool) ($training->reportType->modo_estricto ?? false);
        $examples = $modoEstricto
            ? []
            : $this->selectBestExamples($training, $inputContent, $availableForExamples);

        foreach ($examples as $example) {
            $messages[] = [
                'role' => 'user',
                'content' => "## EJEMPLO DE ENTRADA ({$example['capitulo']})\n\n{$example['input']}",
            ];
            $messages[] = [
                'role' => 'assistant',
                'content' => $example['output'],
            ];
        }

        // Agregar el nuevo input del usuario
        if ($modoEstricto) {
            $userMessage = "## ENTRADA A PROCESAR\n\n{$inputContent}\n\n---\n"
                . "Generá la salida siguiendo EXCLUSIVAMENTE las INSTRUCCIONES OBLIGATORIAS "
                . "del system prompt. No uses formato de ejemplos previos. No agregues "
                . "secciones no solicitadas. No incorpores conocimiento externo.";
        } elseif (!empty($examples)) {
            $userMessage = "## NUEVA ENTRADA A PROCESAR\n\nBasándote en los ejemplos anteriores "
                . "como referencia de estilo, generá el documento de salida para el siguiente "
                . "contenido. Si las instrucciones del usuario en el system prompt contradicen "
                . "el formato de los ejemplos, PRIORIZÁ las instrucciones del usuario.\n\n"
                . "{$inputContent}";
        } else {
            $userMessage = "## ENTRADA A PROCESAR\n\n{$inputContent}\n\n---\n"
                . "Generá la salida siguiendo las instrucciones del system prompt.";
        }

        $messages[] = [
            'role' => 'user',
            'content' => $userMessage,
        ];

        try {
            $customPrompt = $training->reportType->prompt ?? null;
            $totalUsage = ['prompt_tokens' => 0, 'completion_tokens' => 0, 'total_tokens' => 0];
            $generatedContent = '';
            $validationHistory = [];
            $finalValidation = null;

            // Loop de generación + validación + auto-corrección.
            // Iteración 0: generación inicial. Iteraciones 1..N: reintentos con feedback.
            for ($attempt = 0; $attempt <= $this->maxValidationRetries; $attempt++) {
                Log::info("Generando con OpenAI modelo: {$model}, ejemplos: " . count($examples) . ", intento: " . ($attempt + 1));

                $response = OpenAI::chat()->create(array_merge([
                    'model' => $model,
                    'messages' => $messages,
                    'temperature' => 0.1,
                    'top_p' => 0.9,
                ], $this->tokenLimitParam($model, $maxOutputTokens)));

                $generatedContent = $response->choices[0]->message->content;
                $totalUsage['prompt_tokens'] += $response->usage->promptTokens;
                $totalUsage['completion_tokens'] += $response->usage->completionTokens;
                $totalUsage['total_tokens'] += $response->usage->totalTokens;

                $validation = $this->validator->validate($generatedContent, $customPrompt);
                $validationHistory[] = [
                    'attempt' => $attempt + 1,
                    'valid' => $validation['valid'],
                    'violations_count' => count($validation['violations']),
                    'critical_count' => count(array_filter($validation['violations'], fn($v) => $v['severity'] === 'critical')),
                ];
                $finalValidation = $validation;

                Log::info("Intento " . ($attempt + 1) . ": valid={$validation['valid']}, violations=" . count($validation['violations']));

                if ($validation['valid']) {
                    break;
                }

                if ($attempt >= $this->maxValidationRetries) {
                    Log::warning("Loop de validación agotó {$this->maxValidationRetries} reintentos. Aplicando saneo final.");
                    break;
                }

                // Reentrenar la conversación con el feedback de validación.
                // Mantenemos system + último user, agregamos assistant fallido + corrección.
                $messages[] = ['role' => 'assistant', 'content' => $generatedContent];
                $messages[] = ['role' => 'user', 'content' => $validation['feedback_for_ai']];
            }

            // Saneo determinístico final: aunque la IA siga incumpliendo después de N
            // reintentos, removemos las palabras prohibidas a nivel código. Es la garantía
            // que la IA no puede dar.
            $sanitizedContent = $this->sanitizeForbiddenWords($generatedContent, $customPrompt);
            $sanitized = $sanitizedContent !== $generatedContent;
            if ($sanitized) {
                Log::info("Saneo final aplicado: palabras prohibidas removidas a nivel código.");
                $generatedContent = $sanitizedContent;
                $finalValidation = $this->validator->validate($generatedContent, $customPrompt);
            }

            return [
                'success' => true,
                'content' => $generatedContent,
                'model' => $model,
                'examples_used' => count($examples),
                'validation' => [
                    'valid' => $finalValidation['valid'] ?? true,
                    'violations' => $finalValidation['violations'] ?? [],
                    'metrics' => $finalValidation['metrics'] ?? [],
                    'attempts' => count($validationHistory),
                    'history' => $validationHistory,
                    'sanitized_post_hoc' => $sanitized,
                ],
                'usage' => $totalUsage,
            ];

        } catch (\OpenAI\Exceptions\RateLimitException $e) {
            // Extraer el error real del body de la respuesta (puede ser insufficient_quota)
            $errorMessage = 'Rate limit exceeded';
            try {
                $body = json_decode($e->response->getBody()->getContents(), true);
                $errorCode = $body['error']['code'] ?? '';
                $errorDetail = $body['error']['message'] ?? $e->getMessage();
                Log::error("Error OpenAI (RateLimit): code={$errorCode}, message={$errorDetail}");

                if ($errorCode === 'insufficient_quota') {
                    $errorMessage = 'Se ha agotado el crédito de OpenAI. Es necesario agregar fondos en https://platform.openai.com/account/billing. Contacta al administrador.';
                } else {
                    $errorMessage = $this->parseOpenAIError($errorDetail);
                }
            } catch (\Exception $parseEx) {
                Log::error("Error generando salida con IA (RateLimit): " . $e->getMessage());
                $errorMessage = $this->parseOpenAIError($e->getMessage());
            }

            return [
                'success' => false,
                'error' => $errorMessage,
            ];

        } catch (\Exception $e) {
            Log::error("Error generando salida con IA: " . $e->getMessage());

            return [
                'success' => false,
                'error' => $this->parseOpenAIError($e->getMessage()),
            ];
        }
    }

    /**
     * Selecciona los mejores ejemplos para el contexto dado el límite de tokens
     */
    protected function selectBestExamples(AITraining $training, string $newInput, int $maxTokens): array
    {
        // Excluir ejemplos marcados como contaminados o explícitamente excluidos
        // por la auditoría (Fase 3): arrastran al modelo hacia patrones rechazados.
        $examples = $training->examples()
            ->where('excluido_few_shot', false)
            ->where('audit_status', '!=', 'contaminated')
            ->get();

        $selectedExamples = [];
        $usedTokens = 0;

        // Por ahora, seleccionar los más recientes que quepan
        // En el futuro se podría implementar similitud semántica
        foreach ($examples as $example) {
            $inputTruncated = $this->smartTruncate($example->input_content, 3000);
            $outputTruncated = $this->smartTruncate($example->output_content, 4000);

            $exampleTokens = (int)((strlen($inputTruncated) + strlen($outputTruncated)) / 4);

            if ($usedTokens + $exampleTokens > $maxTokens) {
                // Si es el primer ejemplo, al menos incluir uno truncado más agresivamente
                if (empty($selectedExamples)) {
                    $inputTruncated = $this->smartTruncate($example->input_content, 1500);
                    $outputTruncated = $this->smartTruncate($example->output_content, 2000);
                    $selectedExamples[] = [
                        'capitulo' => $example->capitulo,
                        'input' => $inputTruncated,
                        'output' => $outputTruncated,
                    ];
                }
                break;
            }

            $selectedExamples[] = [
                'capitulo' => $example->capitulo,
                'input' => $inputTruncated,
                'output' => $outputTruncated,
            ];

            $usedTokens += $exampleTokens;

            // Máximo 5 ejemplos
            if (count($selectedExamples) >= 5) {
                break;
            }
        }

        return $selectedExamples;
    }

    /**
     * Saneo final determinístico: elimina palabras prohibidas del output.
     * Es la garantía que el LLM (probabilístico) no puede dar.
     * Se aplica solo si la validación falló después de los reintentos.
     */
    protected function sanitizeForbiddenWords(string $output, ?string $customPrompt): string
    {
        if (empty($customPrompt)) return $output;

        $parser = new PromptParserService();
        $forbidden = $parser->extractForbiddenTerms($customPrompt);
        if (empty($forbidden)) return $output;

        // Reemplazos seguros: mantienen el sentido pero quitan la palabra prohibida
        $replacements = [
            'optimizar' => 'ajustar', 'optimización' => 'ajuste', 'optimiza' => 'ajusta',
            'garantizar' => 'sostener', 'garantiza' => 'sostiene', 'garantía' => 'condición',
            'asegurar' => 'mantener', 'asegura' => 'mantiene',
            'implementar' => 'aplicar', 'implementa' => 'aplica', 'implementación' => 'aplicación',
            'ejecutar' => 'realizar', 'ejecuta' => 'realiza', 'ejecución' => 'realización',
            'maximizar' => 'incrementar', 'maximiza' => 'incrementa',
            'efectivo' => 'operativo', 'efectiva' => 'operativa', 'efectividad' => 'operatividad',
            'esencial' => 'requerido', 'esenciales' => 'requeridos',
            'clave' => 'definido', 'fundamental' => 'requerido',
        ];

        foreach ($forbidden as $term) {
            $replacement = $replacements[$term] ?? '';
            // Reemplazo case-preserving aproximado: solo lowercase y capitalize
            $patterns = [
                '/(?<![a-záéíóúüñ])' . preg_quote($term, '/') . '(?![a-záéíóúüñ])/u' => $replacement,
                '/(?<![a-záéíóúüñ])' . preg_quote(mb_convert_case($term, MB_CASE_TITLE, 'UTF-8'), '/') . '(?![a-záéíóúüñ])/u' => mb_convert_case($replacement, MB_CASE_TITLE, 'UTF-8'),
            ];
            foreach ($patterns as $pattern => $replace) {
                $output = preg_replace($pattern, $replace, $output);
            }
        }

        // Limpiar dobles espacios que el reemplazo puede dejar
        $output = preg_replace('/[ \t]{2,}/', ' ', $output);
        $output = preg_replace('/\s+([,.;:])/', '$1', $output);

        return $output;
    }

    /**
     * Trunca el contenido de forma inteligente, intentando no cortar en medio de palabras o secciones
     */
    protected function smartTruncate(string $content, int $maxChars): string
    {
        if (strlen($content) <= $maxChars) {
            return $content;
        }

        // Cortar en un salto de línea si es posible
        $truncated = substr($content, 0, $maxChars);
        $lastNewline = strrpos($truncated, "\n");

        if ($lastNewline !== false && $lastNewline > $maxChars * 0.7) {
            $truncated = substr($content, 0, $lastNewline);
        }

        return $truncated . "\n\n[... contenido adicional omitido para optimizar el contexto ...]";
    }

    /**
     * Trunca el contenido para no exceder un límite de caracteres (método legacy)
     */
    protected function truncateContent(string $content, int $maxChars): string
    {
        return $this->smartTruncate($content, $maxChars);
    }

    /**
     * Parsea errores de OpenAI para mensajes más amigables
     */
    protected function parseOpenAIError(string $message): string
    {
        if (str_contains($message, 'rate_limit')) {
            return 'Se ha excedido el límite de solicitudes a OpenAI. Por favor, espera unos minutos e intenta de nuevo.';
        }
        if (str_contains($message, 'context_length')) {
            return 'El contenido es demasiado extenso para procesar. Intenta con archivos más pequeños.';
        }
        if (str_contains($message, 'invalid_api_key')) {
            return 'La clave de API de OpenAI no es válida. Contacta al administrador.';
        }
        if (str_contains($message, 'insufficient_quota')) {
            return 'Se ha agotado el crédito de OpenAI. Contacta al administrador.';
        }

        return $message;
    }

    /**
     * Verifica si el entrenamiento tiene suficientes ejemplos
     */
    public function hasMinimumExamples(AITraining $training, int $minimum = 1): bool
    {
        return $training->examples_count >= $minimum;
    }

    /**
     * Obtiene estadísticas del entrenamiento
     */
    public function getTrainingStats(AITraining $training): array
    {
        $examples = $training->examples;

        $totalInputSize = $examples->sum(function ($ex) {
            return strlen($ex->input_content);
        });
        $totalOutputSize = $examples->sum(function ($ex) {
            return strlen($ex->output_content);
        });

        return [
            'total_examples' => $examples->count(),
            'total_input_files' => $examples->sum('input_files_count'),
            'chapters' => $examples->pluck('capitulo')->unique()->values()->toArray(),
            'last_trained' => $training->last_trained_at?->format('d/m/Y H:i'),
            'status' => $training->status,
            'total_input_size' => $this->formatBytes($totalInputSize),
            'total_output_size' => $this->formatBytes($totalOutputSize),
            'estimated_tokens' => (int)(($totalInputSize + $totalOutputSize) / 4),
        ];
    }

    /**
     * Formatea bytes a una unidad legible
     */
    protected function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' bytes';
    }

    /**
     * Obtiene los modelos disponibles
     */
    public function getAvailableModels(): array
    {
        return [
            'gpt-5' => 'GPT-5 (Alta calidad, flagship)',
            'gpt-5-mini' => 'GPT-5 Mini (Recomendado, mejor precio/calidad)',
            'gpt-5-nano' => 'GPT-5 Nano (Ultra económico y rápido)',
            'gpt-4o' => 'GPT-4o (Legacy, mayor calidad)',
            'gpt-4o-mini' => 'GPT-4o Mini (Legacy, rápido)',
            'gpt-4-turbo' => 'GPT-4 Turbo (Legacy)',
            'gpt-3.5-turbo' => 'GPT-3.5 Turbo (Legacy, económico)',
        ];
    }

    /**
     * Verifica la conectividad y cuota con la API de OpenAI
     */
    public function checkOpenAIStatus(): array
    {
        try {
            $response = OpenAI::chat()->create(array_merge([
                'model' => 'gpt-5-nano',
                'messages' => [['role' => 'user', 'content' => 'OK']],
            ], $this->tokenLimitParam('gpt-5-nano', 16)));

            return [
                'ok' => true,
                'message' => 'La API de OpenAI está funcionando correctamente.',
            ];
        } catch (\OpenAI\Exceptions\RateLimitException $e) {
            try {
                $body = json_decode($e->response->getBody()->getContents(), true);
                $errorCode = $body['error']['code'] ?? 'rate_limit';

                if ($errorCode === 'insufficient_quota') {
                    return [
                        'ok' => false,
                        'message' => 'Sin crédito en OpenAI. Agrega fondos en https://platform.openai.com/account/billing',
                        'code' => 'insufficient_quota',
                    ];
                }

                return [
                    'ok' => false,
                    'message' => 'Límite de solicitudes excedido. Intenta en unos minutos.',
                    'code' => 'rate_limit',
                ];
            } catch (\Exception $parseEx) {
                return [
                    'ok' => false,
                    'message' => 'Error de límite de solicitudes: ' . $e->getMessage(),
                    'code' => 'rate_limit',
                ];
            }
        } catch (\Exception $e) {
            return [
                'ok' => false,
                'message' => 'Error de conexión con OpenAI: ' . $e->getMessage(),
                'code' => 'connection_error',
            ];
        }
    }
}
