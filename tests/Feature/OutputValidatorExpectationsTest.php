<?php

namespace Tests\Feature;

use App\Services\OutputValidatorService;
use App\Services\PromptParserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La clasificación del proyecto también valida la SALIDA: el Excel declara, por
 * combinación servicio+documento, si el entregable requiere tablas, formatos o
 * diagramas. Si la salida no los trae, se avisa.
 *
 * Los avisos son SIEMPRE warning, nunca critical. Y hay una razón dura: un
 * critical dispara reintento con feedback, y forzar una tabla que el CASO DE
 * REFERENCIA no tiene contradice al Prompt Maestro ("No cambies la estructura,
 * no añadas apartados"). El Excel orienta; el caso de referencia manda.
 */
class OutputValidatorExpectationsTest extends TestCase
{
    // El validador consulta las palabras prohibidas globales (tabla forbidden_words),
    // así que necesita base aunque estos casos no las usen.
    use RefreshDatabase;

    private function validator(): OutputValidatorService
    {
        return new OutputValidatorService(new PromptParserService());
    }

    private function tipos(array $violations): array
    {
        return array_column($violations, 'type');
    }

    public function test_sin_expectativas_no_agrega_violaciones(): void
    {
        $resultado = $this->validator()->validate('Un documento cualquiera.', null);

        $this->assertNotContains('missing_expected_table', $this->tipos($resultado['violations']));
        $this->assertNotContains('missing_expected_diagram', $this->tipos($resultado['violations']));
        $this->assertNotContains('missing_expected_format', $this->tipos($resultado['violations']));
    }

    public function test_avisa_cuando_falta_la_tabla_esperada(): void
    {
        $resultado = $this->validator()->validate(
            "## Resultados\n\nTodo salió bien, sin datos tabulados.",
            null,
            ['requiere_tablas' => 'Sí']
        );

        $this->assertContains('missing_expected_table', $this->tipos($resultado['violations']));
    }

    public function test_no_avisa_si_la_salida_trae_una_tabla_markdown(): void
    {
        $salida = "## Resultados\n\n| Indicador | Valor |\n| --- | --- |\n| Cobertura | 92% |\n";

        $resultado = $this->validator()->validate($salida, null, ['requiere_tablas' => 'Sí']);

        $this->assertNotContains('missing_expected_table', $this->tipos($resultado['violations']));
    }

    public function test_avisa_cuando_falta_el_diagrama_esperado(): void
    {
        $resultado = $this->validator()->validate(
            'Documento sin representación gráfica alguna.',
            null,
            ['requiere_diagrama' => 'Sí']
        );

        $this->assertContains('missing_expected_diagram', $this->tipos($resultado['violations']));
    }

    public function test_no_avisa_si_la_salida_menciona_un_diagrama(): void
    {
        $resultado = $this->validator()->validate(
            'El diagrama de flujo del proceso se presenta a continuación.',
            null,
            ['requiere_diagrama' => 'Sí']
        );

        $this->assertNotContains('missing_expected_diagram', $this->tipos($resultado['violations']));
    }

    public function test_avisa_cuando_faltan_los_formatos_esperados(): void
    {
        $resultado = $this->validator()->validate(
            'Documento pelado, sin nada adjunto.',
            null,
            ['requiere_formatos' => 'Sí']
        );

        $this->assertContains('missing_expected_format', $this->tipos($resultado['violations']));
    }

    public function test_opcional_nunca_genera_aviso(): void
    {
        $resultado = $this->validator()->validate(
            'Documento pelado, sin nada adjunto.',
            null,
            ['requiere_diagrama' => 'Opcional', 'requiere_formatos' => 'Opcional']
        );

        $this->assertNotContains('missing_expected_diagram', $this->tipos($resultado['violations']));
        $this->assertNotContains('missing_expected_format', $this->tipos($resultado['violations']));
    }

    public function test_no_avisa_cuando_el_excel_dice_que_no_hace_falta(): void
    {
        $resultado = $this->validator()->validate(
            'Documento pelado, sin nada adjunto.',
            null,
            ['requiere_diagrama' => 'No']
        );

        $this->assertNotContains('missing_expected_diagram', $this->tipos($resultado['violations']));
    }

    public function test_los_avisos_son_warning_y_no_invalidan_la_salida(): void
    {
        $resultado = $this->validator()->validate(
            'Documento pelado.',
            null,
            ['requiere_tablas' => 'Sí', 'requiere_diagrama' => 'Sí', 'requiere_formatos' => 'Sí']
        );

        $esperados = ['missing_expected_table', 'missing_expected_diagram', 'missing_expected_format'];

        foreach ($resultado['violations'] as $violation) {
            if (in_array($violation['type'], $esperados, true)) {
                $this->assertSame('warning', $violation['severity'], "{$violation['type']} no puede ser critical.");
            }
        }

        // Sin críticos, la generación sigue siendo válida: el Excel orienta, no bloquea.
        $this->assertTrue($resultado['valid']);
    }
}
