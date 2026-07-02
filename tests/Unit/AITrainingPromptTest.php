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
}
