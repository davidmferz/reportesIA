<?php

namespace Tests\Unit;

use App\Services\DocumentExtractorService;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\Exception\InvalidImageException;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ZipArchive;

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

    public function test_docx_con_emf_invalido_extrae_texto_sin_cargar_imagen(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'docx-emf-') . '.docx';
        $this->createDocxWithInvalidEmf($path);

        try {
            $defaultReader = WordIOFactory::createReader('Word2007');
            try {
                $defaultReader->load($path);
                $this->fail('El lector default debe fallar con una imagen EMF invalida.');
            } catch (InvalidImageException) {
                $this->addToAssertionCount(1);
            }

            $service = new DocumentExtractorService();
            $method = new ReflectionMethod($service, 'extractFromDocx');
            $result = $method->invoke($service, $path);

            $this->assertStringContainsString('Texto antes de la imagen.', $result);
            $this->assertStringContainsString('Texto despues de la imagen.', $result);
        } finally {
            @unlink($path);
        }
    }

    private function createDocxWithInvalidEmf(string $path): void
    {
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE));

        $zip->addFromString('[Content_Types].xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Default Extension="emf" ContentType="image/x-emf"/>
  <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
</Types>
XML);

        $zip->addFromString('_rels/.rels', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
</Relationships>
XML);

        $zip->addFromString('word/_rels/document.xml.rels', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rIdImage4" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/image4.emf"/>
</Relationships>
XML);

        $zip->addFromString('word/document.xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"
    xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"
    xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing"
    xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"
    xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture">
  <w:body>
    <w:p><w:r><w:t>Texto antes de la imagen.</w:t></w:r></w:p>
    <w:p>
      <w:r>
        <w:drawing>
          <wp:inline>
            <a:graphic>
              <a:graphicData>
                <pic:pic>
                  <pic:nvPicPr><pic:cNvPr id="4" name="image4.emf"/></pic:nvPicPr>
                  <pic:blipFill><a:blip r:embed="rIdImage4"/></pic:blipFill>
                </pic:pic>
              </a:graphicData>
            </a:graphic>
          </wp:inline>
        </w:drawing>
      </w:r>
    </w:p>
    <w:p><w:r><w:t>Texto despues de la imagen.</w:t></w:r></w:p>
  </w:body>
</w:document>
XML);

        $zip->addFromString('word/media/image4.emf', 'not a valid emf image');
        $this->assertTrue($zip->close());
    }
}
