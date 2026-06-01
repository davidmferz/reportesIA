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
 * El cliente exige que el documento GENERADO se parezca al documento de ENTRADA
 * de referencia (no a la salida del ejemplo). Estos tests blindan esa decisión:
 * el system prompt en modo estándar debe orientar el resultado hacia la ENTRADA.
 */
class AITrainingPromptTest extends TestCase
{
    private function buildStandard(string $customPrompt, array $referenceContents): string
    {
        $service = new AITrainingService(
            new DocumentExtractorService(),
            new OutputValidatorService(new PromptParserService()),
        );
        $rt = new ReportType();
        $rt->nombre = 'Plan agro';

        $method = new ReflectionMethod($service, 'buildStandardSystemPrompt');

        return $method->invoke($service, $rt, $customPrompt, $referenceContents);
    }

    public function test_orienta_el_resultado_hacia_el_documento_de_entrada(): void
    {
        $prompt = $this->buildStandard('', [
            "ANÁLISIS DE INFRAESTRUCTURA\nDesarrollo técnico amplio del proceso.",
        ]);

        // Debe pedir REPLICAR el documento de referencia (la ENTRADA)…
        $this->assertStringContainsStringIgnoringCase('REFERENCIA', $prompt);
        $this->assertStringContainsString('ENTRADA', $prompt);
    }

    public function test_ya_no_empuja_hacia_los_ejemplos_de_salida(): void
    {
        $prompt = $this->buildStandard('', [
            "ANÁLISIS DE INFRAESTRUCTURA\nDesarrollo técnico amplio del proceso.",
        ]);

        // …y NO debe seguir diciendo "parecete a la SALIDA, no a la entrada".
        $this->assertStringNotContainsString('EJEMPLOS DE SALIDA', $prompt);
        $this->assertStringNotContainsString('no a la entrada', $prompt);
    }

    public function test_los_patrones_salen_del_documento_de_referencia(): void
    {
        // El heading del documento de referencia (entrada) debe aparecer como patrón.
        $prompt = $this->buildStandard('', [
            "ANALISIS DE RIEGO TECNIFICADO\nContenido del modelo de referencia.",
        ]);

        $this->assertStringContainsString('ANALISIS DE RIEGO TECNIFICADO', $prompt);
    }
}
