<?php

namespace Tests\Unit;

use App\Services\DocumentExtractorService;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\Element\Table;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class DocumentExtractorServiceTest extends TestCase
{
    /**
     * Invoca el método protegido extractTextFromElement por reflexión.
     */
    private function extract($element): string
    {
        $service = new DocumentExtractorService();
        $method = new ReflectionMethod($service, 'extractTextFromElement');

        return $method->invoke($service, $element);
    }

    /**
     * Un TextRun de PhpWord expone getText() (texto concatenado) Y getElements()
     * (los Text hijos con el MISMO texto). El extractor NO debe contar ambos:
     * eso duplicaba el contenido pegado ("OBJETIVOOBJETIVO", "INTRODUCCIÓNINTRODUCCIÓN").
     */
    public function test_textrun_no_duplica_el_texto(): void
    {
        $run = new TextRun();
        $run->addText('INTRODUCCIÓN');

        $result = trim($this->extract($run));

        $this->assertSame('INTRODUCCIÓN', $result);
    }

    /**
     * Caso real: un TextRun con varios Text hijos. El texto debe aparecer UNA vez,
     * en orden, sin repetirse.
     */
    public function test_textrun_con_varios_hijos_no_se_repite(): void
    {
        $run = new TextRun();
        $run->addText('OBJETIVO: ');
        $run->addText('Diseñar un modelo ');
        $run->addText('de conservación.');

        $result = trim($this->extract($run));

        $this->assertSame('OBJETIVO: Diseñar un modelo de conservación.', $result);
        $this->assertSame(1, substr_count($result, 'OBJETIVO'));
    }

    /**
     * El contenido de una tabla debe seguir extrayéndose (regresión: la celda
     * con un TextRun adentro tampoco debe duplicar).
     */
    public function test_tabla_con_textrun_en_celda_no_duplica(): void
    {
        $table = new Table();
        $table->addRow();
        $cell = $table->addCell();
        $run = $cell->addTextRun();
        $run->addText('CELDA');

        $result = $this->extract($table);

        $this->assertSame(1, substr_count($result, 'CELDA'), "El texto de la celda no debe duplicarse");
        $this->assertStringContainsString('CELDA', $result);
    }
}
