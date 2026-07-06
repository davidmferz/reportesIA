<?php

namespace Tests\Unit;

use App\Models\AIGeneration;
use PHPUnit\Framework\TestCase;

class AIGenerationDisplayOutputTest extends TestCase
{
    private function makeGeneration(?string $output): AIGeneration
    {
        $generation = new AIGeneration();
        $generation->output_content = $output;

        return $generation;
    }

    public function test_contenido_sin_imagenes_queda_intacto(): void
    {
        $contenido = "## Introducción\n\nEste es un párrafo normal con **negritas** y una [liga](https://ejemplo.com).\n\n- Item uno\n- Item dos";

        $this->assertSame($contenido, $this->makeGeneration($contenido)->display_output);
    }

    public function test_contenido_nulo_devuelve_cadena_vacia(): void
    {
        $this->assertSame('', $this->makeGeneration(null)->display_output);
    }

    public function test_imagen_en_linea_propia_se_convierte_en_placeholder_con_descripcion(): void
    {
        $contenido = "Texto previo.\n\n![Diagrama de flujo del proceso](imagen1.png)\n\nTexto posterior.";

        $resultado = $this->makeGeneration($contenido)->display_output;

        $this->assertStringNotContainsString('![', $resultado);
        $this->assertStringNotContainsString('imagen1.png', $resultado);
        $this->assertStringContainsString('> 📷 **Figura:** Diagrama de flujo del proceso', $resultado);
        $this->assertStringContainsString('Texto previo.', $resultado);
        $this->assertStringContainsString('Texto posterior.', $resultado);
    }

    public function test_imagen_sin_descripcion_usa_placeholder_generico(): void
    {
        $contenido = "![](https://ejemplo.com/foto.jpg)";

        $resultado = $this->makeGeneration($contenido)->display_output;

        $this->assertStringNotContainsString('![', $resultado);
        $this->assertStringContainsString('> 📷 **Figura referenciada en el documento original**', $resultado);
    }

    public function test_imagen_dentro_de_un_parrafo_usa_placeholder_en_linea(): void
    {
        $contenido = "Como se observa en ![Gráfica de resultados](chart.png) los valores aumentan.";

        $resultado = $this->makeGeneration($contenido)->display_output;

        $this->assertStringNotContainsString('![', $resultado);
        $this->assertStringNotContainsString('chart.png', $resultado);
        $this->assertStringContainsString('📷 *[Figura: Gráfica de resultados]*', $resultado);
        $this->assertStringContainsString('Como se observa en', $resultado);
    }

    public function test_imagen_con_titulo_tambien_se_reemplaza(): void
    {
        $contenido = '![Mapa del predio](mapa.png "Título del mapa")';

        $resultado = $this->makeGeneration($contenido)->display_output;

        $this->assertStringNotContainsString('mapa.png', $resultado);
        $this->assertStringContainsString('> 📷 **Figura:** Mapa del predio', $resultado);
    }

    public function test_etiqueta_img_html_se_reemplaza(): void
    {
        $contenido = "Párrafo.\n\n<img src=\"data:image/png;base64,iVBORw0KGgo=\" alt=\"Esquema general\">\n\nSigue el texto.";

        $resultado = $this->makeGeneration($contenido)->display_output;

        $this->assertStringNotContainsString('<img', $resultado);
        $this->assertStringNotContainsString('base64', $resultado);
        $this->assertStringContainsString('> 📷 **Figura:** Esquema general', $resultado);
    }

    public function test_etiqueta_img_sin_alt_usa_placeholder_generico(): void
    {
        $contenido = '<img src="/storage/foto.jpg" />';

        $resultado = $this->makeGeneration($contenido)->display_output;

        $this->assertStringNotContainsString('<img', $resultado);
        $this->assertStringContainsString('> 📷 **Figura referenciada en el documento original**', $resultado);
    }

    public function test_multiples_imagenes_se_reemplazan_todas(): void
    {
        $contenido = "![Figura uno](a.png)\n\nTexto medio.\n\n![Figura dos](b.png)";

        $resultado = $this->makeGeneration($contenido)->display_output;

        $this->assertStringNotContainsString('![', $resultado);
        $this->assertStringContainsString('> 📷 **Figura:** Figura uno', $resultado);
        $this->assertStringContainsString('> 📷 **Figura:** Figura dos', $resultado);
    }

    public function test_ligas_normales_no_se_tocan(): void
    {
        $contenido = 'Consultar la [normativa vigente](https://gob.mx/norma) para más detalle.';

        $this->assertSame($contenido, $this->makeGeneration($contenido)->display_output);
    }

    public function test_bloque_separado_por_tabs_se_convierte_en_tabla_markdown(): void
    {
        $contenido = "Esquema 1. Resumen de parámetros\n"
            . "Parámetro\tObservación técnica\tRelevancia\n"
            . "pH/CE del agua\tMedición necesaria\tDeterminante\n"
            . "Presencia biológica\tAlgas y biofilm\tObstrucción de emisores\n"
            . "\nTexto posterior.";

        $resultado = $this->makeGeneration($contenido)->display_output;

        $this->assertStringContainsString('| Parámetro | Observación técnica | Relevancia |', $resultado);
        $this->assertStringContainsString('| --- | --- | --- |', $resultado);
        $this->assertStringContainsString('| pH/CE del agua | Medición necesaria | Determinante |', $resultado);
        $this->assertStringContainsString('| Presencia biológica | Algas y biofilm | Obstrucción de emisores |', $resultado);
        $this->assertStringNotContainsString("\t", $resultado);
        $this->assertStringContainsString('Texto posterior.', $resultado);
    }

    public function test_tabla_precedida_por_titulo_recibe_linea_en_blanco(): void
    {
        $contenido = "Tabla 1. Compatibilidad técnica\n"
            . "Componente\tEspecificación\n"
            . "Cintilla\tAutocompensada";

        $resultado = $this->makeGeneration($contenido)->display_output;

        // Sin línea en blanco entre el título y la tabla, GFM absorbe la tabla en el párrafo
        $this->assertStringContainsString("Tabla 1. Compatibilidad técnica\n\n| Componente | Especificación |", $resultado);
    }

    public function test_linea_suelta_con_tab_no_se_convierte_en_tabla(): void
    {
        $contenido = "Párrafo normal.\n\nValor\tsuelto con un tab\n\nOtro párrafo.";

        $resultado = $this->makeGeneration($contenido)->display_output;

        $this->assertStringNotContainsString('| --- |', $resultado);
        $this->assertStringContainsString("Valor\tsuelto con un tab", $resultado);
    }

    public function test_filas_con_distinto_numero_de_columnas_se_normalizan(): void
    {
        $contenido = "Col A\tCol B\tCol C\n"
            . "uno\tdos\n"
            . "tres\tcuatro\tcinco";

        $resultado = $this->makeGeneration($contenido)->display_output;

        $this->assertStringContainsString('| Col A | Col B | Col C |', $resultado);
        $this->assertStringContainsString('| uno | dos |  |', $resultado);
        $this->assertStringContainsString('| tres | cuatro | cinco |', $resultado);
    }

    public function test_pipes_dentro_de_celdas_se_escapan(): void
    {
        $contenido = "Col A\tCol B\n"
            . "valor con | pipe\totro valor";

        $resultado = $this->makeGeneration($contenido)->display_output;

        $this->assertStringContainsString('valor con \\| pipe', $resultado);
    }

    public function test_contenido_sin_tabs_ni_imagenes_queda_identico(): void
    {
        $contenido = "## Sección\n\nPárrafo con texto normal.\n\n- Lista uno\n- Lista dos";

        $this->assertSame($contenido, $this->makeGeneration($contenido)->display_output);
    }
}
