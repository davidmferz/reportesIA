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
    protected string $defaultModel = 'gpt-4o-mini';

    /**
     * Límites de tokens por modelo
     */
    protected array $modelLimits = [
        'gpt-4o-mini' => ['context' => 128000, 'output' => 16384],
        'gpt-4o' => ['context' => 128000, 'output' => 16384],
        'gpt-4-turbo' => ['context' => 128000, 'output' => 4096],
        'gpt-3.5-turbo' => ['context' => 16385, 'output' => 4096],
    ];

    public function __construct(DocumentExtractorService $extractor)
    {
        $this->extractor = $extractor;
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
     * Construye un prompt del sistema mejorado analizando los patrones de los ejemplos
     */
    protected function buildEnhancedSystemPrompt(ReportType $reportType, array $outputContents): string
    {
        // Analizar patrones comunes en las salidas
        $patternAnalysis = $this->analyzeOutputPatterns($outputContents);

        // Obtener el prompt personalizado del tipo de reporte si existe
        $customPrompt = $reportType->prompt ? trim($reportType->prompt) : '';
        $customPromptSection = '';

        if (!empty($customPrompt)) {
            $customPromptSection = <<<CUSTOM

## INSTRUCCIONES PERSONALIZADAS DEL USUARIO
{$customPrompt}

CUSTOM;
        }

        $prompt = <<<PROMPT
Eres un asistente experto especializado en generar documentos del tipo "{$reportType->nombre}".
{$customPromptSection}
## TU ROL
Tu tarea principal es transformar documentos de entrada en documentos de salida siguiendo exactamente el formato, estructura y estilo que has aprendido de los ejemplos de entrenamiento proporcionados.

## PROCESO DE ANÁLISIS
Cuando recibas nuevos documentos de entrada:
1. **EXTRAE** toda la información relevante de los documentos de entrada
2. **IDENTIFICA** los datos clave, cifras, nombres, fechas y conceptos importantes
3. **TRANSFORMA** esta información siguiendo el patrón de los ejemplos de entrenamiento
4. **GENERA** una salida coherente y completa

## PATRONES IDENTIFICADOS EN LOS EJEMPLOS
{$patternAnalysis}

## REGLAS IMPORTANTES
- Mantén SIEMPRE el mismo formato y estructura que los ejemplos de entrenamiento
- Usa la terminología y estilo consistente con los ejemplos
- Si hay datos específicos en la entrada (números, nombres, fechas), úsalos en la salida
- No inventes información que no esté en los documentos de entrada
- Si falta información necesaria, indica claramente qué falta

## FORMATO DE SALIDA
- Genera el contenido en formato Markdown para mejor legibilidad
- Usa encabezados (##, ###), listas (- o *) y tablas cuando sea apropiado
- Mantén un formato profesional y bien estructurado
- Incluye todas las secciones que aparecen en los ejemplos de entrenamiento
PROMPT;

        return $prompt;
    }

    /**
     * Analiza los patrones comunes en los contenidos de salida
     */
    protected function analyzeOutputPatterns(array $outputContents): string
    {
        if (empty($outputContents)) {
            return "No se detectaron patrones específicos.";
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

        // Agregar ejemplos de entrenamiento como contexto (few-shot learning)
        $examples = $this->selectBestExamples($training, $inputContent, $availableForExamples);

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
        $messages[] = [
            'role' => 'user',
            'content' => "## NUEVA ENTRADA A PROCESAR\n\nBasándote en los ejemplos anteriores, genera el documento de salida correspondiente para el siguiente contenido:\n\n{$inputContent}\n\n---\nGenera la salida completa siguiendo exactamente el mismo formato y estructura de los ejemplos de entrenamiento.",
        ];

        try {
            Log::info("Generando con OpenAI modelo: {$model}, ejemplos: " . count($examples));

            $response = OpenAI::chat()->create([
                'model' => $model,
                'messages' => $messages,
                'max_tokens' => $maxOutputTokens,
                'temperature' => 0.3, // Más bajo para mayor consistencia
                'top_p' => 0.9,
            ]);

            $generatedContent = $response->choices[0]->message->content;

            Log::info("Generación exitosa. Tokens: prompt={$response->usage->promptTokens}, completion={$response->usage->completionTokens}");

            return [
                'success' => true,
                'content' => $generatedContent,
                'model' => $model,
                'examples_used' => count($examples),
                'usage' => [
                    'prompt_tokens' => $response->usage->promptTokens,
                    'completion_tokens' => $response->usage->completionTokens,
                    'total_tokens' => $response->usage->totalTokens,
                ],
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
        $examples = $training->examples()->get();
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
            'gpt-4o-mini' => 'GPT-4o Mini (Rápido y económico)',
            'gpt-4o' => 'GPT-4o (Mayor calidad)',
            'gpt-4-turbo' => 'GPT-4 Turbo (Potente)',
            'gpt-3.5-turbo' => 'GPT-3.5 Turbo (Más económico)',
        ];
    }

    /**
     * Verifica la conectividad y cuota con la API de OpenAI
     */
    public function checkOpenAIStatus(): array
    {
        try {
            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [['role' => 'user', 'content' => 'OK']],
                'max_tokens' => 3,
            ]);

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
