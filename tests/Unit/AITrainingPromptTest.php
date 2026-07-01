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
 * El cliente exige que el documento GENERADO se parezca al archivo de SALIDA
 * del entrenamiento. Estos tests blindan esa decisión: el system prompt en modo
 * estándar debe orientar el resultado hacia la SALIDA, igual que el juez.
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

    public function test_orienta_el_resultado_hacia_el_archivo_de_salida(): void
    {
        $prompt = $this->buildStandard('', [
            "ANÁLISIS DE INFRAESTRUCTURA\nDesarrollo técnico amplio del proceso.",
        ]);

        // Debe pedir REPLICAR el archivo de referencia (la SALIDA)…
        $this->assertStringContainsStringIgnoringCase('REFERENCIA', $prompt);
        $this->assertStringContainsString('SALIDA', $prompt);
    }

    public function test_ya_no_empuja_hacia_los_documentos_de_entrada(): void
    {
        $prompt = $this->buildStandard('', [
            "ANÁLISIS DE INFRAESTRUCTURA\nDesarrollo técnico amplio del proceso.",
        ]);

        // …y NO debe seguir diciendo que el molde son los documentos de ENTRADA.
        $this->assertStringNotContainsString('documentos de ENTRADA', $prompt);
        $this->assertStringNotContainsString('no a la salida', $prompt);
    }

    public function test_runtime_override_reemplaza_entrada_por_salida_sin_reentrenar(): void
    {
        $service = new AITrainingService(
            new DocumentExtractorService(),
            new OutputValidatorService(new PromptParserService()),
        );

        $method = new ReflectionMethod($service, 'referenceModelPolicyPrompt');
        $policy = $method->invoke($service);

        $this->assertStringContainsString('SALIDA', $policy);
        $this->assertStringContainsString('reemplazada', $policy);
        $this->assertStringContainsString('ENTRADA', $policy);
    }

    public function test_los_patrones_salen_del_documento_de_referencia(): void
    {
        // El heading del archivo de salida de referencia debe aparecer como patrón.
        $prompt = $this->buildStandard('', [
            "ANALISIS DE RIEGO TECNIFICADO\nContenido del modelo de referencia.",
        ]);

        $this->assertStringContainsString('ANALISIS DE RIEGO TECNIFICADO', $prompt);
    }
}
