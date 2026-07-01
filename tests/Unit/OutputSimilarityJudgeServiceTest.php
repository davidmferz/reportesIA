<?php

namespace Tests\Unit;

use App\Services\OutputSimilarityJudgeService;
use PHPUnit\Framework\TestCase;

class OutputSimilarityJudgeServiceTest extends TestCase
{
    public function test_aprueba_cuando_el_contenido_generado_se_parece_a_una_salida_de_entrenamiento(): void
    {
        $judge = new OutputSimilarityJudgeService();

        $reference = "ANÁLISIS TÉCNICO DEL SISTEMA DE RIEGO\n\n"
            . "El sistema de riego presenta reservorio, cabezal de filtrado, tuberías principales "
            . "y cintilla de goteo. Se revisó presión, caudal, uniformidad y eficiencia operativa.\n\n"
            . "CONCLUSIONES\nEl manejo hidráulico requiere seguimiento preventivo y registro de mantenimiento.";

        $generated = "ANÁLISIS TÉCNICO DEL SISTEMA DE RIEGO\n\n"
            . "El sistema de riego integra reservorio, cabezal de filtrado, tuberías principales "
            . "y cintilla. La revisión considera presión, caudal, uniformidad de aplicación y eficiencia operativa.\n\n"
            . "CONCLUSIONES\nSe recomienda seguimiento preventivo y control del mantenimiento hidráulico.";

        $result = $judge->judge($generated, [
            ['id' => 10, 'grupo_id' => 'riego', 'capitulo' => 'Riego', 'output_content' => $reference],
        ]);

        $this->assertTrue($result['valid']);
        $this->assertGreaterThanOrEqual($result['threshold'], $result['best_score']);
        $this->assertSame(10, $result['best_reference']['id']);
        $this->assertSame([], $result['violations']);
    }

    public function test_marca_violacion_critica_cuando_no_hay_similitud_con_las_salidas_de_entrenamiento(): void
    {
        $judge = new OutputSimilarityJudgeService();

        $reference = "ANÁLISIS TÉCNICO DEL SISTEMA DE RIEGO\nReservorio, cabezal, tuberías, válvulas y cintilla de goteo.";
        $generated = "RECETA DE PANADERÍA\nMezclar harina, levadura, azúcar y mantequilla. Hornear hasta dorar.";

        $result = $judge->judge($generated, [
            ['id' => 11, 'grupo_id' => 'riego', 'capitulo' => 'Riego', 'output_content' => $reference],
        ]);

        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['violations']);
        $this->assertSame('training_output_similarity_below_threshold', $result['violations'][0]['type']);
        $this->assertSame('critical', $result['violations'][0]['severity']);
        $this->assertNotEmpty($result['feedback_for_ai']);
        $this->assertStringContainsString('Encabezados de referencia', $result['feedback_for_ai']);
    }

    public function test_no_falla_si_no_hay_salidas_de_referencia_utilizables(): void
    {
        $judge = new OutputSimilarityJudgeService();

        $result = $judge->judge('Contenido generado.', []);

        $this->assertTrue($result['valid']);
        $this->assertSame('skipped_no_references', $result['status']);
        $this->assertSame([], $result['violations']);
    }
}
