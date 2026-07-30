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

    protected ?OutputSimilarityJudgeService $similarityJudge;

    /**
     * Servicio de investigación por internet. Opcional: se resuelve perezosamente
     * vía el container si no se inyecta, para no romper instanciaciones manuales
     * (p. ej. tests) que construyen el servicio con dos argumentos.
     */
    protected ?WebResearchService $webResearch;

    /**
     * Traductor de la clasificación del catálogo a encuadre de prompt y expectativas
     * de formato. Lazy por la misma razón que webResearch: no romper las
     * instanciaciones manuales de dos argumentos que ya existen en los tests.
     */
    protected ?CatalogContextService $catalogContext = null;

    /**
     * Máximo de reintentos del loop de auto-corrección.
     * Más de 2 da rendimientos decrecientes y consume tokens innecesarios.
     */
    protected int $maxValidationRetries = 2;

    public function __construct(
        DocumentExtractorService $extractor,
        OutputValidatorService $validator,
        ?WebResearchService $webResearch = null,
        ?OutputSimilarityJudgeService $similarityJudge = null
    ) {
        $this->extractor = $extractor;
        $this->validator = $validator;
        $this->webResearch = $webResearch;
        $this->similarityJudge = $similarityJudge;
    }

    /**
     * Resuelve el servicio de investigación web (lazy) si no fue inyectado.
     */
    protected function webResearch(): WebResearchService
    {
        return $this->webResearch ??= app(WebResearchService::class);
    }

    protected function similarityJudge(): OutputSimilarityJudgeService
    {
        return $this->similarityJudge ??= app(OutputSimilarityJudgeService::class);
    }

    protected function catalogContext(): CatalogContextService
    {
        return $this->catalogContext ??= app(CatalogContextService::class);
    }

    /**
     * GPT-5 (y modelos de razonamiento o1/o3) cambiaron el nombre del parámetro:
     * 'max_tokens' fue reemplazado por 'max_completion_tokens'. GPT-4 y anteriores
     * todavía aceptan el viejo. Devolvemos el array con el nombre correcto según el modelo.
     */
    protected function tokenLimitParam(string $model, int $maxTokens): array
    {
        if ($this->isReasoningModel($model)) {
            return ['max_completion_tokens' => $maxTokens];
        }
        return ['max_tokens' => $maxTokens];
    }

    /**
     * GPT-5 y modelos de razonamiento (o1/o3) solo aceptan el valor por default
     * de 'temperature' y 'top_p' (1). Mandar cualquier otro valor revienta con
     * "Unsupported value: 'temperature' does not support X". Para GPT-4 sí
     * podemos controlar el sampling para que la salida sea más determinística.
     */
    protected function samplingParams(string $model): array
    {
        if ($this->isReasoningModel($model)) {
            return [];
        }
        return [
            'temperature' => 0.1,
            'top_p' => 0.9,
        ];
    }

    protected function isReasoningModel(string $model): bool
    {
        return str_starts_with($model, 'gpt-5')
            || str_starts_with($model, 'o1')
            || str_starts_with($model, 'o3');
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

            foreach ($fileGroups as $grupoId => $files) {
                $archivosEntrada = $files->where('tipo_archivo', 'entrada');
                $archivoSalida = $files->where('tipo_archivo', 'salida')->first();

                if ($archivosEntrada->isEmpty() || !$archivoSalida) {
                    continue;
                }

                // Extraer contenido de archivos de entrada
                $inputContent = '';
                foreach ($archivosEntrada as $archivo) {
                    $extractedInput = $this->extractor->extractText($archivo->ruta);
                    $this->assertExtractionUsable($extractedInput, $archivo->nombre_original, $archivo->ruta);
                    $inputContent .= "--- Archivo: {$archivo->nombre_original} ---\n";
                    $inputContent .= $extractedInput;
                    $inputContent .= "\n\n";
                }

                // Extraer contenido del archivo de salida
                $outputContent = $this->extractor->extractText($archivoSalida->ruta);
                $this->assertExtractionUsable($outputContent, $archivoSalida->nombre_original, $archivoSalida->ruta);

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

            // Construir el prompt del sistema. El Prompt Maestro (Anexo de la proposal)
            // ya enseña el patrón de transformación ENTRADA→SALIDA vía few-shot; no
            // hace falta análisis textual de patrones sobre las salidas.
            $systemPrompt = $this->buildEnhancedSystemPrompt($reportType);

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
     * Garantiza que el texto extraído de un archivo del entrenamiento sea utilizable.
     * Histórico: si el extractor fallaba, devolvía "[Error al extraer DOCX: ...]" como
     * si fuera contenido válido; el ejemplo quedaba con basura de ~50 chars y el few-shot
     * mostraba esa basura a la IA. Cortamos acá para que el training falle ruidoso.
     */
    protected function assertExtractionUsable(string $extracted, string $originalName, string $path): void
    {
        $trimmed = trim($extracted);

        if ($trimmed === '' || str_starts_with($trimmed, '[Error al extraer')) {
            throw new \RuntimeException("No se pudo extraer texto del archivo '{$originalName}' ({$path}). Contenido devuelto: {$trimmed}");
        }

        $fullPath = \Illuminate\Support\Facades\Storage::disk('local')->path($path);
        $fileSize = file_exists($fullPath) ? filesize($fullPath) : 0;
        // Heurística: un docx > 50KB que extrae menos de 200 chars es casi seguro un fallo
        // silencioso de extracción (PhpWord se traga elementos que no sabe leer).
        if ($fileSize > 51200 && strlen($trimmed) < 200) {
            throw new \RuntimeException("El archivo '{$originalName}' pesa " . number_format($fileSize) . " bytes pero solo extrajo " . strlen($trimmed) . " caracteres. Probable fallo silencioso del extractor.");
        }
    }

    /**
     * Construye un prompt del sistema. Las instrucciones del cliente tienen MÁXIMA prioridad
     * y NUNCA pueden ser contradichas por reglas del sistema.
     */
    protected function buildEnhancedSystemPrompt(ReportType $reportType): string
    {
        $customPrompt = $reportType->prompt ? trim($reportType->prompt) : '';
        $modoEstricto = (bool) ($reportType->modo_estricto ?? false);

        if ($modoEstricto && !empty($customPrompt)) {
            return $this->buildStrictSystemPrompt($reportType, $customPrompt);
        }

        return $this->buildStandardSystemPrompt($reportType, $customPrompt);
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
     * Modo estándar: el Prompt Maestro (Anexo de la proposal "Integrar el Prompt
     * Maestro en la generación estándar") es la ÚNICA instrucción canónica. Enseña
     * al modelo, vía el caso de referencia (few-shot en generateOutput), el patrón
     * de transformación ENTRADA→SALIDA. Texto literal — no parafrasear: el cliente
     * pidió exactamente esta redacción.
     */
    protected function buildStandardSystemPrompt(ReportType $reportType, string $customPrompt): string
    {
        $customPromptSection = '';
        if (!empty($customPrompt)) {
            $customPromptSection = <<<CUSTOM

## INSTRUCCIONES DEL USUARIO (PRIORIDAD MÁXIMA)
Estas instrucciones son la PRIMERA ley. Si alguna regla posterior del maestro las
contradice, GANAN estas instrucciones. No las amplíes ni las interpretes.

{$customPrompt}

CUSTOM;
        }

        return <<<PROMPT
Eres un consultor experto especializado en elaborar documentos técnicos.

Tu objetivo es aprender la forma de redactar del ejemplo proporcionado.
{$customPromptSection}
Fase 1. Aprendizaje

Se te proporcionarán:

- Uno o varios documentos de entrada del CASO DE REFERENCIA.
- El documento final generado para ese caso.

Debes analizar ambos conjuntos de documentos y decidir:

- qué información se extrae de los documentos de entrada
- qué información se descarta
- qué información se resume
- qué conclusiones se generan
- qué estilo de redacción utiliza el consultor
- qué estructura tiene el documento
- qué títulos aparecen
- qué tablas utiliza
- qué formato tienen las listas
- qué tono emplea
- qué longitud suele tener cada apartado

No debes copiar literalmente el contenido.

Debes aprender el patrón de transformación.

Fase 2. Generación

Después recibirás un nuevo conjunto de documentos de entrada.

Tu tarea consiste en generar un documento nuevo siguiendo exactamente:

- la misma estructura
- los mismos títulos
- el mismo orden
- el mismo nivel de detalle
- el mismo estilo de escritura
- el mismo tipo de conclusiones
- el mismo formato de tablas
- el mismo formato de numeración

La salida debe parecer redactada por la misma persona que realizó el documento de referencia.

Reglas

Nunca inventes datos.

Si un dato no aparece en los documentos de entrada:

- deja el apartado vacío indicando [Información no disponible]
- o indícalo explícitamente en las observaciones.

Si varios documentos contienen información contradictoria:

- indícalo.

Si falta información necesaria para completar un apartado:

- indícalo.

No cambies la estructura del documento.

No elimines apartados.

No añadas apartados nuevos.

Objetivo principal

Si los documentos de entrada fueran exactamente iguales a los del caso de referencia, el documento generado debería ser prácticamente idéntico al documento de salida de referencia.

Si cambian únicamente algunos datos, únicamente deben cambiar las partes relacionadas con esos datos.

Todo el resto del documento debe permanecer igual.

Calidad

Antes de responder verifica:

- que todos los apartados del documento original existen
- que ninguna sección ha desaparecido
- que la numeración coincide
- que las tablas conservan el mismo formato
- que todas las conclusiones están justificadas por la documentación de entrada.

Genera únicamente el documento final.
PROMPT;
    }

    /**
     * Arma los pares user/assistant del few-shot (Fase 1 del maestro): cada caso de
     * referencia se presenta como DOCUMENTOS DE ENTRADA + DOCUMENTO FINAL GENERADO,
     * y el assistant confirma el aprendizaje del patrón. Método puro (sin llamadas a
     * OpenAI) para poder testear el few-shot sin mockear la API.
     */
    /**
     * Mensaje system de "Reglas para la obtención de información" que acompaña al
     * brief de internet cuando usa_internet está activo y la búsqueda devolvió
     * datos (used=true). El texto es VERBATIM (exigencia del cliente): define la
     * jerarquía de fuentes (documentos de entrada > internet > "[Información no
     * disponible]") y los usos permitidos/prohibidos de los datos externos.
     * Extraído como seam puro (sin llamar a OpenAI) para poder testear el
     * contrato textual, igual que buildReferenceExampleMessages().
     */
    /**
     * Mensaje de sistema de FORMATO DE SALIDA. Solo presentación (Markdown válido):
     * no toca contenido, fidelidad ni vocabulario — esos contratos viven en el
     * Prompt Maestro / system_prompt persistido. Sin esto, el modelo emite tablas
     * con tabuladores o listas con rayas que el visor GFM aplana como párrafos,
     * y referencias a imágenes que no puede producir. Se envía en ambos modos.
     */
    protected function buildOutputFormatMessage(): string
    {
        return <<<'TEXTO'
FORMATO DE SALIDA (solo presentación; no altera ninguna regla de contenido):
El documento se renderiza como Markdown (GitHub Flavored). Emití SIEMPRE Markdown válido:

1. TABLAS: usá exclusivamente la sintaxis de tabla Markdown con pipes:
| Columna A | Columna B |
| --- | --- |
| dato | dato |
NUNCA construyas tablas con tabuladores, ni con listas de viñetas usando rayas (—) o guiones para simular columnas. Si la fuente presenta datos tabulares, la salida DEBE ser una tabla con pipes.

2. TÍTULOS DE TABLA: la leyenda (ej. "Tabla 1. Descripción") va en su propia línea, separada de la tabla por una línea en blanco. Los encabezados de columna van DENTRO de la tabla, nunca en la leyenda.

3. ENCABEZADOS DE SECCIÓN: usá ## para secciones y ### para subsecciones. Nunca dejes un título como línea suelta sin marcador.

4. LISTAS: usá - para viñetas y 1. para numeradas. Las rayas (—) solo como puntuación dentro de una oración, jamás como separador de columnas.

5. IMÁGENES: no podés producir imágenes reales. NUNCA emitas sintaxis de imagen (![...](...) ni <img>). Si la fuente contiene una figura, representá su información como tabla o texto descriptivo.
TEXTO;
    }

    protected function buildInternetRulesMessage(string $brief): string
    {
        $reglas = <<<'TEXTO'
Reglas para la obtención de información

La información debe obtenerse siguiendo este orden de prioridad:

1. Los documentos de entrada proporcionados.
2. Información pública y verificable disponible en Internet.
3. Si la información no puede obtenerse de ninguna de las dos fuentes anteriores, indicar [Información no disponible].

Nunca sustituyas información específica del caso por información genérica encontrada en Internet.

La información obtenida en Internet únicamente debe utilizarse para:

- completar datos objetivos que falten;
- identificar legislación aplicable;
- obtener información pública sobre empresas, organismos o productos;
- completar definiciones técnicas;
- ampliar información normativa;
- localizar estándares o referencias oficiales.

Nunca inventes información.

Si utilizas información obtenida en Internet:

- intégrala de forma natural en el documento;
- verifica que procede de fuentes fiables;
- asegúrate de que es coherente con el resto de la documentación aportada.

Si existen discrepancias entre los documentos de entrada y la información encontrada en Internet, prevalecerá siempre la información contenida en los documentos de entrada y la discrepancia deberá indicarse en el apartado de observaciones.

Si tras consultar Internet la información sigue sin poder determinarse, indicar:

[Información no disponible]
TEXTO;

        return $reglas . "\n\nDATOS OBTENIDOS DE INTERNET (con fuentes citadas):\n\n" . $brief;
    }

    protected function buildReferenceExampleMessages(array $examples): array
    {
        $messages = [];
        $numero = 0;

        foreach ($examples as $example) {
            $numero++;
            $capitulo = $example['capitulo'] ?? null;
            $titulo = "## CASO DE REFERENCIA {$numero}" . ($capitulo ? " ({$capitulo})" : '');

            $messages[] = [
                'role' => 'user',
                'content' => "{$titulo}\n\n"
                    . "### DOCUMENTOS DE ENTRADA\n\n"
                    . ($example['input'] ?? '') . "\n\n"
                    . "### DOCUMENTO FINAL GENERADO\n\n"
                    . ($example['output'] ?? ''),
            ];
            $messages[] = [
                'role' => 'assistant',
                'content' => "Comprendido. Aprendí el patrón de transformación de este caso de referencia "
                    . "(Fase 1): qué información se extrae, descarta y resume, qué conclusiones se generan, "
                    . "y qué estructura, títulos, tablas, listas, tono y longitud utiliza. Lo aplicaré al "
                    . "generar el nuevo documento (Fase 2).",
            ];
        }

        return $messages;
    }

    /**
     * Salidas de entrenamiento que el juez usa como referencia.
     *
     * Se priorizan ejemplos habilitados para few-shot y no contaminados. Si por
     * alguna razón todos quedaron excluidos, hacemos fallback a todos los ejemplos:
     * el cliente pidió validar contra los archivos de salida del entrenamiento, no
     * dejar la validación muda.
     */
    protected function referenceOutputsForSimilarity(AITraining $training): \Illuminate\Support\Collection
    {
        $usable = $training->examples()
            ->where('excluido_few_shot', false)
            ->where('audit_status', '!=', 'contaminated')
            ->get();

        return $usable->isNotEmpty()
            ? $usable
            : $training->examples()->get();
    }

    /**
     * Une la validación determinística del prompt con el juez de similitud contra
     * las salidas de entrenamiento. Una generación es válida solo si pasa ambas.
     *
     * @param array<string, mixed> $promptValidation
     * @param array<string, mixed> $similarityValidation
     * @return array<string, mixed>
     */
    protected function mergeValidationResults(array $promptValidation, array $similarityValidation): array
    {
        $feedbackParts = array_filter([
            $promptValidation['feedback_for_ai'] ?? null,
            $similarityValidation['feedback_for_ai'] ?? null,
        ]);

        $promptMetrics = $promptValidation['metrics'] ?? [];
        $promptMetrics['training_output_similarity'] = [
            'status' => $similarityValidation['status'] ?? null,
            'threshold' => $similarityValidation['threshold'] ?? null,
            'best_score' => $similarityValidation['best_score'] ?? null,
            'best_reference' => $similarityValidation['best_reference'] ?? null,
            'comparisons' => $similarityValidation['comparisons'] ?? [],
        ];

        return array_merge($promptValidation, [
            'valid' => (bool) ($promptValidation['valid'] ?? true)
                && (bool) ($similarityValidation['valid'] ?? true),
            'violations' => array_merge(
                $promptValidation['violations'] ?? [],
                $similarityValidation['violations'] ?? []
            ),
            'metrics' => $promptMetrics,
            'feedback_for_ai' => !empty($feedbackParts) ? implode("\n\n", $feedbackParts) : null,
        ]);
    }

    /**
     * Valida el contenido generado con dos compuertas:
     * 1) reglas explícitas del prompt/admin;
     * 2) similitud contra los archivos de salida del entrenamiento.
     */
    protected function validateGeneratedOutput(string $generatedContent, ?string $customPrompt, iterable $similarityReferences, array $expectations = []): array
    {
        $promptValidation = $this->validator->validate($generatedContent, $customPrompt, $expectations);
        $similarityValidation = $this->similarityJudge()->judge($generatedContent, $similarityReferences);

        return $this->mergeValidationResults($promptValidation, $similarityValidation);
    }

    /**
     * Genera una salida basándose en el entrenamiento y nuevos archivos de entrada
     */
    public function generateOutput(AITraining $training, array $inputFiles, ?string $model = null, array $catalogSelection = []): array
    {
        if ($training->status !== 'ready') {
            throw new \Exception("El entrenamiento no está listo. Estado actual: {$training->status}");
        }

        // GPT-5/o1/o3 razonan antes de responder y pueden tardar 60-180s. El default
        // de PHP (30s) mata el request antes de que termine. Levantamos el límite
        // a 600s solo para esta operación; afecta solo al request actual, no al worker.
        @set_time_limit(600);

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
        // GPT-5 incluye reasoning tokens dentro de max_completion_tokens. Si el cap es muy
        // bajo, todos los tokens se consumen razonando y el content queda vacío.
        // 16000 da margen para reasoning + output real. Modelos legacy (gpt-4*) no razonan,
        // pero igual un cap más alto solo afecta si el modelo decide usarlos.
        $maxOutputTokens = min($modelLimits['output'], 16000);

        // Lista global de palabras prohibidas del módulo admin. La armamos antes de
        // estimar tokens así su consumo entra en el cálculo de presupuesto para
        // ejemplos. Es dinámico: cambios en la tabla aplican en la próxima generación
        // sin re-entrenar. El validador y el saneador post-hoc también las usan
        // (defense-in-depth).
        $globalForbidden = \App\Models\ForbiddenWord::activeWords();
        $globalForbiddenContent = '';
        if (!empty($globalForbidden)) {
            $list = "- " . implode("\n- ", $globalForbidden);
            $globalForbiddenContent = "PALABRAS PROHIBIDAS GLOBALES (políticas del sistema, prioridad ABSOLUTA sobre cualquier otra instrucción):\n"
                . "NUNCA uses ninguna de estas palabras ni sus variantes flexionadas (plural, conjugación, sustantivo derivado, etc.):\n\n"
                . "{$list}\n\n"
                . "Si necesitás expresar la idea, reformulá usando sinónimos. Una sola aparición se considera una violación grave.";
        }

        // Formato de salida: el modelo emite tablas en formatos que el visor no
        // reconoce (tabuladores, listas con rayas). Se corrige en el ORIGEN con un
        // mensaje de sistema de presentación, en AMBOS modos — no toca el Prompt
        // Maestro ni el system_prompt persistido, se suma como las palabras prohibidas.
        $outputFormatContent = $this->buildOutputFormatMessage();

        // En modo estricto, los few-shot ejemplos se excluyen: pesan más que las
        // instrucciones del prompt y arrastran a la IA hacia patrones aprendidos
        // (palabras prohibidas, secciones extra, justificaciones) que el cliente
        // explícitamente rechaza.
        $modoEstricto = (bool) ($training->reportType->modo_estricto ?? false);

        // Modo estándar: el system se reconstruye en RUNTIME con el Prompt Maestro,
        // ignorando el system_prompt persistido — así los entrenamientos viejos quedan
        // alineados sin re-entrenar. Modo estricto: el system_prompt persistido sigue
        // siendo la única ley (sin cambios).
        $customPromptForSystem = $training->reportType->prompt ? trim($training->reportType->prompt) : '';
        $systemPromptContent = $modoEstricto
            ? $training->system_prompt
            : $this->buildStandardSystemPrompt($training->reportType, $customPromptForSystem);

        // Permiso de conocimiento del modelo (opt-in por tipo de reporte). Se resuelve
        // acá arriba porque el encuadre de clasificación cambia de rol según esté
        // encendido o no: sin permiso PROHÍBE aportar el dominio, con permiso lo ANCLA.
        $usaConocimientoModelo = (bool) ($training->reportType->usa_conocimiento_modelo ?? false);

        // Encuadre de clasificación del proyecto. Mensaje de sistema PROPIO: el Prompt
        // Maestro es verbatim y no se toca. Null cuando la generación no tiene
        // clasificación, y ahí no se envía nada — cero cambio de comportamiento.
        $catalogContextContent = $this->catalogContext()->promptMessage($catalogSelection, $usaConocimientoModelo);

        // Estimar tokens del sistema y entrada (aproximado: 4 chars = 1 token).
        // Sumamos los mensajes adicionales porque también ocupan contexto. Usamos el
        // prompt runtime real (no el system_prompt persistido) para que el presupuesto
        // de ejemplos refleje lo que efectivamente se envía en modo estándar.
        $systemTokens = (int)((strlen($systemPromptContent) + strlen($globalForbiddenContent) + strlen($outputFormatContent) + strlen((string) $catalogContextContent)) / 4);
        $inputTokens = (int)(strlen($inputContent) / 4);
        $reserveForOutput = $maxOutputTokens;

        $availableForExamples = $maxContextTokens - $systemTokens - $inputTokens - $reserveForOutput - 1000; // Margen de seguridad

        // Construir los mensajes para la API de OpenAI
        $messages = [
            [
                'role' => 'system',
                'content' => $systemPromptContent,
            ],
        ];

        if ($globalForbiddenContent !== '') {
            $messages[] = [
                'role' => 'system',
                'content' => $globalForbiddenContent,
            ];
        }

        $messages[] = [
            'role' => 'system',
            'content' => $outputFormatContent,
        ];

        // Permiso de conocimiento del modelo (solo si el tipo lo tiene habilitado).
        // Override en RUNTIME (no requiere re-entrenar): relaja la fidelidad estricta
        // para que el modelo APORTE conocimiento general del dominio. Default OFF: los
        // tipos existentes mantienen "solo datos del cliente". El permiso es acotado:
        // jamás autoriza inventar datos específicos del cliente.
        if ($usaConocimientoModelo) {
            $messages[] = [
                'role' => 'system',
                'content' => "PERMISO DE CONOCIMIENTO DEL MODELO (tiene prioridad sobre cualquier regla "
                    . "previa de 'no incorporar conocimiento externo' o 'no inventes').\n"
                    . "Usá tu propio conocimiento experto del dominio para ENRIQUECER y CONTEXTUALIZAR el "
                    . "documento (definiciones, marco técnico, buenas prácticas del sector), ADEMÁS de los "
                    . "datos del cliente.\n"
                    . "REGLAS INNEGOCIABLES:\n"
                    . "1) NO inventes datos ESPECÍFICOS del cliente (cifras, nombres, fechas, resultados, "
                    . "mediciones) que no estén en la entrada: esos salen EXCLUSIVAMENTE del documento.\n"
                    . "2) Tu aporte es conocimiento GENERAL del campo, nunca hechos atribuidos al cliente.\n"
                    . "3) No contradigas ni reemplaces los datos de la entrada.",
            ];
        }

        // Encuadre de clasificación. Va DESPUÉS del permiso a propósito: cuando el
        // permiso está encendido, este mensaje lo ancla a un dominio concreto, y para
        // eso el permiso ya tiene que estar dicho.
        if ($catalogContextContent !== null) {
            $messages[] = [
                'role' => 'system',
                'content' => $catalogContextContent,
            ];
        }

        // Enriquecimiento con datos de internet (solo si el tipo de reporte lo tiene
        // habilitado). Es ADITIVO y FAIL-SAFE: si la búsqueda falla o no aplica, la
        // generación continúa igual, sin datos externos. Los tipos con el toggle
        // apagado (el default) no pagan ningún costo ni cambian su comportamiento.
        if ((bool) ($training->reportType->usa_internet ?? false)) {
            $research = $this->webResearch()->research($inputContent, $model);
            if ($research['used']) {
                Log::info('Datos de internet incorporados a la generación. Fuentes: ' . count($research['sources']));
                $messages[] = [
                    'role' => 'system',
                    'content' => $this->buildInternetRulesMessage($research['brief']),
                ];
            }
        }

        // Pre-extraemos los límites de palabras del prompt del cliente para que el
        // truncado de los few-shot escale con el pedido. Sin esto, un cap fijo
        // (ej. 4000 chars ≈ 600 palabras) ahorca al modelo cuando el usuario pide
        // ">2000 palabras" — el few-shot demuestra una longitud que CONTRADICE la
        // instrucción y la IA imita el ejemplo, no la regla.
        $wordLimitsForExamples = (new PromptParserService())->extractWordLimits(
            $training->reportType->prompt ?? null
        );
        $examples = $modoEstricto
            ? []
            : $this->selectBestExamples($training, $inputContent, $availableForExamples, $wordLimitsForExamples);

        // Few-shot: cada ejemplo se presenta como par ENTRADA→SALIDA (Fase 1 del
        // maestro), no solo la salida — así el modelo aprende qué se extrae, descarta
        // y resume de la entrada, no solo cómo luce el documento final.
        $messages = array_merge($messages, $this->buildReferenceExampleMessages($examples));

        // Agregar el nuevo input del usuario
        if ($modoEstricto) {
            $clausulaConocimiento = $usaConocimientoModelo
                ? "Podés enriquecer con tu conocimiento experto del dominio, sin inventar datos específicos del cliente."
                : "No incorpores conocimiento externo.";
            $userMessage = "## ENTRADA A PROCESAR\n\n{$inputContent}\n\n---\n"
                . "Generá la salida siguiendo EXCLUSIVAMENTE las INSTRUCCIONES OBLIGATORIAS "
                . "del system prompt. No uses formato de ejemplos previos. No agregues "
                . "secciones no solicitadas. {$clausulaConocimiento}";
        } elseif (!empty($examples)) {
            $clausulaFuente = $usaConocimientoModelo
                ? "Usá los siguientes datos como fuente de los HECHOS específicos del cliente (cifras, nombres, "
                    . "fechas) y COMPLEMENTÁ con tu conocimiento experto del dominio para enriquecer el desarrollo; "
                    . "no inventes datos del cliente que no estén acá."
                : "Usá EXCLUSIVAMENTE los siguientes datos como fuente de información (cifras, nombres, "
                    . "fechas, hechos); no inventes nada que no esté acá.";
            // Fase 2 del maestro: replicar estructura, títulos, orden, detalle y estilo
            // del CASO DE REFERENCIA sobre la nueva entrada — no solo "parecerse a la salida".
            $userMessage = "## NUEVOS DOCUMENTOS DE ENTRADA (Fase 2)\n\n"
                . "Generá el documento nuevo siguiendo exactamente la misma estructura, los mismos "
                . "títulos, el mismo orden, el mismo nivel de detalle, el mismo estilo de escritura, "
                . "el mismo tipo de conclusiones, el mismo formato de tablas y el mismo formato de "
                . "numeración que el CASO DE REFERENCIA. {$clausulaFuente} Si las instrucciones del "
                . "usuario en el system prompt contradicen el formato del caso de referencia, "
                . "PRIORIZÁ las instrucciones del usuario.\n\n"
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
            $similarityReferences = $this->referenceOutputsForSimilarity($training);
            // Requisitos de formato que el catálogo declara para el entregable elegido.
            // Generan avisos (warning), nunca bloquean ni disparan reintento.
            $catalogExpectations = $this->catalogContext()->expectations($catalogSelection);
            $totalUsage = ['prompt_tokens' => 0, 'completion_tokens' => 0, 'total_tokens' => 0];
            $generatedContent = '';
            $validationHistory = [];
            $finalValidation = null;

            // Snapshot del prompt enviado en el primer intento. Se persiste en la
            // generación para auditoría/UI. No incluye los reintentos con feedback
            // (esos quedan en validation_result.history).
            $promptMessagesSnapshot = $messages;

            // Loop de generación + validación + auto-corrección.
            // Iteración 0: generación inicial. Iteraciones 1..N: reintentos con feedback.
            for ($attempt = 0; $attempt <= $this->maxValidationRetries; $attempt++) {
                Log::info("Generando con OpenAI modelo: {$model}, ejemplos: " . count($examples) . ", intento: " . ($attempt + 1) . ", max_output_tokens: {$maxOutputTokens}");

                $response = OpenAI::chat()->create(array_merge([
                    'model' => $model,
                    'messages' => $messages,
                ], $this->samplingParams($model), $this->tokenLimitParam($model, $maxOutputTokens)));

                $generatedContent = $response->choices[0]->message->content ?? '';
                $finishReason = $response->choices[0]->finishReason ?? 'unknown';
                $totalUsage['prompt_tokens'] += $response->usage->promptTokens;
                $totalUsage['completion_tokens'] += $response->usage->completionTokens;
                $totalUsage['total_tokens'] += $response->usage->totalTokens;

                // GPT-5/o1/o3 incluyen tokens de razonamiento dentro de completion_tokens.
                // Si reasoning_tokens consume todo el límite, content viene vacío con finish_reason="length".
                $rawResponse = $response->toArray();
                $reasoningTokens = $rawResponse['usage']['completion_tokens_details']['reasoning_tokens'] ?? 0;

                Log::info("Respuesta OpenAI: finish_reason={$finishReason}, completion_tokens={$response->usage->completionTokens}, reasoning_tokens={$reasoningTokens}, content_length=" . strlen(trim($generatedContent)));

                // Si el modelo devuelve contenido vacío, no tiene sentido validar ni reintentar
                // — eso solo gasta más tokens. Detectamos la causa raíz y devolvemos error claro.
                if (trim($generatedContent) === '') {
                    Log::error("Modelo {$model} devolvió contenido vacío. finish_reason={$finishReason}, reasoning_tokens={$reasoningTokens}, max_output={$maxOutputTokens}. Raw response: " . json_encode($rawResponse));

                    if ($finishReason === 'length') {
                        $errorDetail = "El modelo {$model} agotó el límite de tokens ({$maxOutputTokens}) antes de generar contenido"
                            . ($reasoningTokens > 0 ? " — gastó {$reasoningTokens} tokens en razonamiento interno." : ".")
                            . " Probá con un modelo más liviano (gpt-5-mini o gpt-5-nano) o reducí el tamaño del prompt/entrada.";
                    } else {
                        $errorDetail = "El modelo {$model} devolvió una respuesta vacía (finish_reason={$finishReason}). Probá nuevamente o cambiá de modelo.";
                    }

                    return [
                        'success' => false,
                        'error' => $errorDetail,
                    ];
                }

                $validation = $this->validateGeneratedOutput($generatedContent, $customPrompt, $similarityReferences, $catalogExpectations);
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
                $finalValidation = $this->validateGeneratedOutput($generatedContent, $customPrompt, $similarityReferences, $catalogExpectations);
            }

            // Truncado determinístico final por límite de palabras. Los LLMs no cuentan
            // palabras (cuentan tokens), por lo que el modelo puede pasarse del máximo
            // incluso después de varios reintentos con feedback explícito. Si todavía
            // estamos fuera del límite, cortamos en el último final de oración que
            // entre dentro del cap. Misma filosofía que el saneo de palabras: el LLM
            // es probabilístico, el código es determinístico.
            $wordLimits = (new PromptParserService())->extractWordLimits($customPrompt);
            $truncated = false;
            if ($wordLimits['max'] !== null) {
                $truncatedContent = $this->truncateToWordLimit($generatedContent, $wordLimits['max']);
                if ($truncatedContent !== $generatedContent) {
                    Log::info("Truncado final aplicado: contenido cortado al límite de {$wordLimits['max']} palabras.");
                    $generatedContent = $truncatedContent;
                    $truncated = true;
                    $finalValidation = $this->validateGeneratedOutput($generatedContent, $customPrompt, $similarityReferences, $catalogExpectations);
                }
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
                    'truncated_post_hoc' => $truncated,
                ],
                'usage' => $totalUsage,
                'prompt_messages' => $promptMessagesSnapshot,
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
     * Selecciona los mejores ejemplos para el contexto dado el límite de tokens.
     *
     * Los caps de truncado escalan con el pedido del usuario: si el prompt pide
     * "más de N palabras" como mínimo, el output del ejemplo tiene que demostrar
     * AL MENOS N palabras — sino el few-shot contradice la instrucción y la IA
     * imita el ejemplo, ignorando el "más de N". Asumimos ~8 chars/palabra en
     * español (con margen) para convertir el límite a chars.
     *
     * @param array{min: int|null, max: int|null} $wordLimits
     */
    protected function selectBestExamples(AITraining $training, string $newInput, int $maxTokens, array $wordLimits = ['min' => null, 'max' => null]): array
    {
        // Excluir ejemplos marcados como contaminados o explícitamente excluidos
        // por la auditoría (Fase 3): arrastran al modelo hacia patrones rechazados.
        $examples = $training->examples()
            ->where('excluido_few_shot', false)
            ->where('audit_status', '!=', 'contaminated')
            ->get();

        // Caps por defecto subidos: con gpt-5-* tenemos 400K tokens de contexto.
        // El cap viejo (4000 chars) era residual de cuando el contexto era estrecho.
        // Si el usuario fijó un mínimo de palabras, el ejemplo tiene que cubrirlo
        // para no enseñarle al modelo una longitud más corta que la pedida.
        $defaultInputCap = 8000;
        $defaultOutputCap = 16000;
        if ($wordLimits['min'] !== null) {
            $defaultOutputCap = max($defaultOutputCap, (int) ($wordLimits['min'] * 8));
        }
        if ($wordLimits['max'] !== null) {
            // Si hay máximo, no hace falta mostrar más que ese tope (con margen).
            $defaultOutputCap = min($defaultOutputCap, (int) ($wordLimits['max'] * 8));
        }

        // Caps de fallback (cuando el primer ejemplo no entra entero en el budget).
        // Mantienen al menos la mitad del default para que el ejemplo siga siendo
        // representativo en longitud.
        $fallbackInputCap = (int) ($defaultInputCap / 2);
        $fallbackOutputCap = (int) ($defaultOutputCap / 2);

        $selectedExamples = [];
        $usedTokens = 0;

        // Por ahora, seleccionar los más recientes que quepan
        // En el futuro se podría implementar similitud semántica
        foreach ($examples as $example) {
            $inputTruncated = $this->smartTruncate($example->input_content, $defaultInputCap);
            $outputTruncated = $this->smartTruncate($example->output_content, $defaultOutputCap);

            $exampleTokens = (int)((strlen($inputTruncated) + strlen($outputTruncated)) / 4);

            if ($usedTokens + $exampleTokens > $maxTokens) {
                // Si es el primer ejemplo, al menos incluir uno truncado más agresivamente
                if (empty($selectedExamples)) {
                    $inputTruncated = $this->smartTruncate($example->input_content, $fallbackInputCap);
                    $outputTruncated = $this->smartTruncate($example->output_content, $fallbackOutputCap);
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
        $parser = new PromptParserService();
        // Mergeamos términos del prompt + lista global del módulo admin. Aunque
        // el customPrompt sea null, las globales pueden aplicar igual.
        $forbidden = array_values(array_unique(array_merge(
            $parser->extractForbiddenTerms($customPrompt),
            \App\Models\ForbiddenWord::activeWords()
        )));
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
     * Truncado final determinístico al límite de palabras. Si el modelo se pasó del
     * máximo y los reintentos no lo arreglaron, cortamos acá. Estrategia:
     *   1. Encontramos cada palabra Unicode (\p{L}\p{N}+) y su offset en el contenido.
     *   2. Si las palabras totales no superan el límite, devolvemos tal cual.
     *   3. Cortamos justo después de la N-ésima palabra (por offset).
     *   4. Buscamos hacia atrás la última oración terminada (.!?) seguida de espacio o
     *      fin de string, y cortamos ahí — preserva el cierre limpio.
     *   5. Si no hay terminador en el fragmento, cortamos en límite de palabra y
     *      anexamos un puntos suspensivos para señalar el corte.
     */
    protected function truncateToWordLimit(string $content, int $maxWords): string
    {
        if ($maxWords <= 0) return $content;

        if (!preg_match_all('/[\p{L}\p{N}]+/u', $content, $matches, PREG_OFFSET_CAPTURE)) {
            return $content;
        }

        $wordHits = $matches[0];
        if (count($wordHits) <= $maxWords) {
            return $content;
        }

        // Offset (en bytes) justo después de la última palabra permitida.
        $lastIndex = $maxWords - 1;
        $cutPos = $wordHits[$lastIndex][1] + strlen($wordHits[$lastIndex][0]);
        $candidate = substr($content, 0, $cutPos);

        // Buscamos el último .!? seguido de espacio/salto de línea/fin de string.
        // 's' = dotall, 'u' = unicode. El greedy .* fuerza el ÚLTIMO match.
        if (preg_match('/^.*[.!?](?=\s|$)/su', $candidate, $m)) {
            return rtrim($m[0]);
        }

        // Sin terminador de oración: corte limpio al final de la última palabra.
        return rtrim($candidate) . '…';
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
