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
}
