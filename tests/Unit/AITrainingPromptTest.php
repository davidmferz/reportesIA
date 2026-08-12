<?php

namespace Tests\Unit;

use App\Models\ReportType;
use App\Services\AITrainingService;
use App\Services\DocumentExtractorService;
use App\Services\OutputValidatorService;
use App\Services\PromptParserService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * El cliente exige una única instrucción canónica para el modo estándar: el Prompt
 * Maestro (Anexo de la proposal "Integrar el Prompt Maestro en la generación
 * estándar"), que enseña al modelo el patrón de transformación ENTRADA→SALIDA a
 * partir de un caso de referencia. Estos tests blindan el contrato textual del
 * maestro, la subordinación de las instrucciones del cliente, la intangibilidad
 * del modo estricto y el nuevo few-shot de pares entrada→salida.
 */
class AITrainingPromptTest extends TestCase
{
    private function service(): AITrainingService
    {
        return new AITrainingService(
            new DocumentExtractorService(),
            new OutputValidatorService(new PromptParserService()),
        );
    }

    private function buildStandard(string $customPrompt = ''): string
    {
        $service = $this->service();
        $rt = new ReportType();
        $rt->nombre = 'Plan agro';

        $method = new ReflectionMethod($service, 'buildStandardSystemPrompt');

        return $method->invoke($service, $rt, $customPrompt);
    }

    public function test_mensaje_de_formato_de_salida_exige_markdown_valido(): void
    {
        $service = $this->service();
        $method = new ReflectionMethod($service, 'buildOutputFormatMessage');

        $mensaje = $method->invoke($service);

        // Tablas: sintaxis pipe obligatoria, formatos inventados prohibidos
        $this->assertStringContainsString('FORMATO DE SALIDA', $mensaje);
        $this->assertStringContainsString('| Columna A | Columna B |', $mensaje);
        $this->assertStringContainsString('| --- | --- |', $mensaje);
        $this->assertStringContainsString('tabuladores', $mensaje);
        $this->assertStringContainsString('rayas', $mensaje);

        // Encabezados de sección con #
        $this->assertStringContainsString('##', $mensaje);

        // Imágenes: el modelo no puede producirlas
        $this->assertStringContainsString('![', $mensaje);
        $this->assertStringContainsString('imágenes', $mensaje);
    }

    public function test_mensaje_de_formato_no_toca_contenido_solo_presentacion(): void
    {
        $service = $this->service();
        $method = new ReflectionMethod($service, 'buildOutputFormatMessage');

        $mensaje = $method->invoke($service);

        $this->assertStringContainsString('presentación', $mensaje);
        $this->assertStringNotContainsString('inventes', $mensaje);
        $this->assertStringNotContainsString('conocimiento', $mensaje);
    }

    public function test_standard_system_prompt_es_el_prompt_maestro(): void
    {
        $prompt = $this->buildStandard();

        $this->assertStringContainsString('consultor experto', $prompt);
        $this->assertStringContainsString('Fase 1', $prompt);
        $this->assertStringContainsString('Fase 2', $prompt);
        $this->assertStringContainsString('patrón de transformación', $prompt);
    }

    public function test_reglas_del_maestro_presentes(): void
    {
        $prompt = $this->buildStandard();

        $this->assertStringContainsString('Nunca inventes datos', $prompt);
        $this->assertStringContainsString('[Información no disponible]', $prompt);
    }

    public function test_instrucciones_cliente_subordinadas(): void
    {
        $prompt = $this->buildStandard('El documento debe tener máximo 2000 palabras.');

        $this->assertStringContainsString('INSTRUCCIONES DEL USUARIO', $prompt);
        $this->assertStringContainsString('PRIORIDAD MÁXIMA', $prompt);
        $this->assertStringContainsString('El documento debe tener máximo 2000 palabras.', $prompt);
        // El maestro debe seguir presente aunque haya instrucciones del cliente.
        $this->assertStringContainsString('Fase 1', $prompt);
        $this->assertStringContainsString('consultor experto', $prompt);
    }

    public function test_modo_estricto_intacto(): void
    {
        $service = $this->service();
        $rt = new ReportType();
        $rt->nombre = 'Plan agro';

        $method = new ReflectionMethod($service, 'buildStrictSystemPrompt');
        $prompt = $method->invoke($service, $rt, 'Instrucción exclusiva del cliente.');

        $this->assertStringContainsString('INSTRUCCIONES OBLIGATORIAS', $prompt);
        $this->assertStringNotContainsString('Fase 1', $prompt);
    }

    public function test_few_shot_pares_entrada_salida(): void
    {
        $service = $this->service();
        $method = new ReflectionMethod($service, 'buildReferenceExampleMessages');

        $messages = $method->invoke($service, [
            [
                'capitulo' => 'Riego tecnificado',
                'input' => 'DATOS CRUDOS: superficie 120 ha, cultivo soja.',
                'output' => 'ANALISIS DE RIEGO TECNIFICADO: la superficie evaluada...',
            ],
        ]);

        $this->assertCount(2, $messages);

        [$userMessage, $assistantMessage] = $messages;

        $this->assertSame('user', $userMessage['role']);
        $this->assertStringContainsString('DOCUMENTOS DE ENTRADA', $userMessage['content']);
        $this->assertStringContainsString('DOCUMENTO FINAL GENERADO', $userMessage['content']);
        $this->assertStringContainsString('DATOS CRUDOS: superficie 120 ha, cultivo soja.', $userMessage['content']);
        $this->assertStringContainsString('ANALISIS DE RIEGO TECNIFICADO: la superficie evaluada...', $userMessage['content']);

        $this->assertSame('assistant', $assistantMessage['role']);
        $this->assertStringContainsString('Fase 1', $assistantMessage['content']);
    }

    private function buildUserMessage(
        bool $modoEstricto = false,
        bool $usaConocimientoModelo = false,
        bool $tieneEjemplos = true
    ): string {
        $service = $this->service();
        $method = new ReflectionMethod($service, 'buildGenerationUserMessage');

        return $method->invoke(
            $service,
            '### Archivo: entrada.docx',
            $modoEstricto,
            $usaConocimientoModelo,
            $tieneEjemplos
        );
    }

    /**
     * El informe de prueba del cliente (docs/Informe de Prueba de IA 2) documenta que
     * su prompt correctivo "sí fue leído pero perdió". La causa verificada: el mensaje
     * user final REPETÍA la Fase 2 del maestro ("misma estructura, mismos títulos,
     * mismo orden"), ocupando el slot de recencia con la orden que el prompt del
     * cliente contradice. La regla ya vive completa en el system: repetirla acá solo
     * le daba a la contradicción la última palabra.
     */
    public function test_mensaje_de_generacion_no_repite_la_orden_de_clonar_estructura(): void
    {
        $mensaje = $this->buildUserMessage();

        $this->assertStringNotContainsString('la misma estructura', $mensaje);
        $this->assertStringNotContainsString('los mismos títulos', $mensaje);
        $this->assertStringNotContainsString('el mismo orden', $mensaje);
        $this->assertStringNotContainsString('el mismo formato de numeración', $mensaje);
        $this->assertStringNotContainsString('CASO DE REFERENCIA', $mensaje);
    }

    /**
     * El desempate estrecho ("si contradicen el FORMATO del caso de referencia") no
     * cubría el conflicto real, que es de estructura y de reglas de contenido. Se
     * elimina de acá: la autoridad se declara una sola vez, en el system, junto a las
     * instrucciones del usuario.
     */
    public function test_mensaje_de_generacion_delega_en_el_system_prompt(): void
    {
        $mensaje = $this->buildUserMessage();

        $this->assertStringContainsString('system prompt', $mensaje);
        $this->assertStringNotContainsString('contradicen el formato', $mensaje);
        $this->assertStringContainsString('### Archivo: entrada.docx', $mensaje);
    }

    /**
     * clausulaFuente es territorio de la regla "Nunca inventes datos" y del toggle
     * usa_conocimiento_modelo: NO se toca en este paso. Solo se blinda que siga
     * conmutando igual.
     */
    public function test_mensaje_de_generacion_conserva_la_clausula_de_fuente(): void
    {
        $sinPermiso = $this->buildUserMessage(usaConocimientoModelo: false);
        $conPermiso = $this->buildUserMessage(usaConocimientoModelo: true);

        $this->assertStringContainsString('EXCLUSIVAMENTE', $sinPermiso);
        $this->assertStringContainsString('conocimiento experto del dominio', $conPermiso);
        $this->assertStringNotContainsString('EXCLUSIVAMENTE', $conPermiso);
    }

    public function test_mensaje_de_generacion_modo_estricto_intacto(): void
    {
        $mensaje = $this->buildUserMessage(modoEstricto: true);

        $this->assertStringContainsString('ENTRADA A PROCESAR', $mensaje);
        $this->assertStringContainsString('INSTRUCCIONES OBLIGATORIAS', $mensaje);
        $this->assertStringContainsString('No incorpores conocimiento externo', $mensaje);
    }

    public function test_mensaje_de_generacion_sin_ejemplos_intacto(): void
    {
        $mensaje = $this->buildUserMessage(tieneEjemplos: false);

        $this->assertStringContainsString('ENTRADA A PROCESAR', $mensaje);
        $this->assertStringContainsString('instrucciones del system prompt', $mensaje);
    }

    /**
     * La cláusula de autoridad decía solo "si alguna regla posterior del maestro las
     * contradice". El conflicto que el cliente documentó no viene de una regla: viene
     * del CASO DE REFERENCIA arrastrando estructura, títulos y apartados. La autoridad
     * tiene que nombrarlo explícitamente o no cubre el caso.
     */
    public function test_autoridad_del_usuario_cubre_el_caso_de_referencia(): void
    {
        $prompt = $this->buildStandard('Definí la estructura según la entrada actual.');

        // Normalizamos espacios: el prompt va en heredoc y el wrapping parte frases.
        // El contrato es el texto, no dónde cae el salto de línea.
        $plano = preg_replace('/\s+/', ' ', $prompt);

        $this->assertStringContainsString('PRIORIDAD MÁXIMA', $plano);
        $this->assertStringContainsString('GANAN estas instrucciones', $plano);
        // La autoridad nombra explícitamente lo que arrastra el ejemplo.
        $this->assertStringContainsString(
            'los apartados o el formato del CASO DE REFERENCIA',
            $plano
        );
        // El alcance viejo, limitado a "reglas posteriores del maestro", no alcanzaba.
        $this->assertStringNotContainsString('Si alguna regla posterior del maestro', $plano);
    }

    public function test_autoridad_del_usuario_ausente_sin_prompt_del_cliente(): void
    {
        $prompt = $this->buildStandard();

        $this->assertStringNotContainsString('PRIORIDAD MÁXIMA', $prompt);
        $this->assertStringNotContainsString('GANAN estas instrucciones', $prompt);
    }

    /**
     * El turno assistant del few-shot es andamiaje NUESTRO, no texto del cliente, y
     * es la señal de dirección más fuerte del stack: le pone al modelo en la boca su
     * propio compromiso. Decía "aprendí qué estructura, títulos [...] y lo aplicaré",
     * contradiciendo la Fase 1 del propio maestro ("No debes copiar literalmente el
     * contenido. Debes aprender el patrón de transformación"). Se limita a acusar
     * recibo del aprendizaje: quién gobierna la Fase 2 es el system, no este turno.
     */
    public function test_turno_assistant_no_se_compromete_a_clonar_estructura(): void
    {
        $service = $this->service();
        $method = new ReflectionMethod($service, 'buildReferenceExampleMessages');

        $messages = $method->invoke($service, [
            ['capitulo' => 'Riego', 'input' => 'Entrada.', 'output' => 'Salida.'],
        ]);

        $assistant = $messages[1]['content'];

        $this->assertStringContainsString('Fase 1', $assistant);
        $this->assertStringContainsString('patrón de transformación', $assistant);
        $this->assertStringContainsString('No copiaré literalmente', $assistant);
        $this->assertStringNotContainsString('títulos', $assistant);
        $this->assertStringNotContainsString('Lo aplicaré', $assistant);
    }

    public function test_no_existe_referenceModelPolicyPrompt(): void
    {
        $service = $this->service();

        $this->assertFalse(method_exists($service, 'referenceModelPolicyPrompt'));

        $method = new ReflectionMethod($service, 'buildReferenceExampleMessages');
        $messages = $method->invoke($service, [
            [
                'capitulo' => 'Riego tecnificado',
                'input' => 'Entrada de ejemplo.',
                'output' => 'Salida de ejemplo.',
            ],
        ]);

        foreach ($messages as $message) {
            $this->assertStringNotContainsString('POLÍTICA ACTUAL DE REFERENCIA', $message['content']);
        }
    }

    /**
     * El cliente exige un texto verbatim de "Reglas para la obtención de información"
     * cuando usa_internet está activo y la búsqueda devolvió resultados (used=true).
     * Este mensaje REEMPLAZA al encabezado genérico anterior ("DATOS COMPLEMENTARIOS
     * OBTENIDOS DE INTERNET..."). Extraído a un seam puro (buildInternetRulesMessage)
     * para poder blindar el contrato textual sin llamar a OpenAI, igual que
     * buildReferenceExampleMessages().
     */
    public function test_reglas_de_internet_verbatim(): void
    {
        $service = $this->service();
        $method = new ReflectionMethod($service, 'buildInternetRulesMessage');

        $brief = 'Brief de ejemplo con datos y FUENTES: https://ejemplo.com';
        $mensaje = $method->invoke($service, $brief);

        $this->assertStringContainsString('Reglas para la obtención de información', $mensaje);
        $this->assertStringContainsString('Los documentos de entrada proporcionados', $mensaje);
        $this->assertStringContainsString('[Información no disponible]', $mensaje);
        $this->assertStringContainsString(
            'prevalecerá siempre la información contenida en los documentos de entrada',
            $mensaje
        );
        $this->assertStringContainsString($brief, $mensaje);
    }

    /**
     * El bloque verbatim reemplaza por completo al encabezado viejo: no debe quedar
     * texto residual de la versión anterior ("DATOS COMPLEMENTARIOS OBTENIDOS DE
     * INTERNET" / "NUNCA contradigas ni reemplaces los datos crudos"), porque
     * conviven mal con la nueva jerarquía de prioridades del cliente.
     */
    public function test_reglas_de_internet_reemplazan_encabezado_viejo(): void
    {
        $service = $this->service();
        $method = new ReflectionMethod($service, 'buildInternetRulesMessage');

        $mensaje = $method->invoke($service, 'Brief de ejemplo.');

        $this->assertStringNotContainsString('DATOS COMPLEMENTARIOS OBTENIDOS DE INTERNET', $mensaje);
        $this->assertStringNotContainsString('NUNCA contradigas ni reemplaces los datos crudos', $mensaje);
    }
}
