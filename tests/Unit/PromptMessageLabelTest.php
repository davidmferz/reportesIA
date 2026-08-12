<?php

namespace Tests\Unit;

use App\Services\PromptMessageLabelService;
use PHPUnit\Framework\TestCase;

/**
 * La vista de auditoría rotulaba "Palabras prohibidas globales" a TODO mensaje system
 * con índice > 0, cuando en realidad se arman hasta seis bloques distintos. El cliente
 * lo detectó en su informe de prueba y tuvo que bautizarlos él mismo ("Prompt de
 * Tablas", "Prompt de Sectores") para poder razonar sobre cuál causaba qué.
 *
 * El rótulo se deriva del CONTENIDO, no de la posición, a propósito: así las
 * generaciones ya guardadas —incluidas las dos pruebas del cliente— quedan bien
 * rotuladas de forma retroactiva, sin migración ni re-generación.
 */
class PromptMessageLabelTest extends TestCase
{
    public function test_el_primer_bloque_son_las_instrucciones_del_entrenamiento(): void
    {
        $this->assertSame(
            'Instrucciones del entrenamiento',
            PromptMessageLabelService::label('Eres un consultor experto. Fase 1. Aprendizaje', 0)
        );
    }

    public function test_el_primer_bloque_en_modo_estricto_tambien(): void
    {
        $this->assertSame(
            'Instrucciones del entrenamiento',
            PromptMessageLabelService::label('INSTRUCCIONES OBLIGATORIAS del cliente.', 0)
        );
    }

    /**
     * El bug exacto que reportó el cliente: un bloque de formato en posición 2 salía
     * rotulado como palabras prohibidas.
     */
    public function test_formato_de_salida_no_se_rotula_como_palabras_prohibidas(): void
    {
        $etiqueta = PromptMessageLabelService::label(
            "FORMATO DE SALIDA (solo presentación; no altera ninguna regla de contenido):",
            2
        );

        $this->assertSame('Formato de salida', $etiqueta);
        $this->assertNotSame('Palabras prohibidas globales', $etiqueta);
    }

    public function test_palabras_prohibidas_se_reconocen_por_contenido(): void
    {
        $this->assertSame(
            'Palabras prohibidas globales',
            PromptMessageLabelService::label('PALABRAS PROHIBIDAS GLOBALES (políticas del sistema...)', 1)
        );
    }

    public function test_permiso_de_conocimiento(): void
    {
        $this->assertSame(
            'Permiso de conocimiento del modelo',
            PromptMessageLabelService::label('PERMISO DE CONOCIMIENTO DEL MODELO (tiene prioridad...)', 3)
        );
    }

    public function test_clasificacion_del_proyecto(): void
    {
        $this->assertSame(
            'Clasificación del proyecto',
            PromptMessageLabelService::label(
                'CLASIFICACIÓN DEL PROYECTO (encuadre para esta generación; no es fuente de datos):',
                4
            )
        );
    }

    public function test_datos_de_internet(): void
    {
        $this->assertSame(
            'Datos de internet',
            PromptMessageLabelService::label('Reglas para la obtención de información', 5)
        );
    }

    /**
     * Un bloque que no reconocemos NO debe heredar el rótulo de otro: ese fue
     * exactamente el defecto. Cae en un genérico numerado, que es honesto.
     */
    public function test_bloque_desconocido_cae_en_generico_numerado(): void
    {
        $this->assertSame(
            'Bloque de sistema 4',
            PromptMessageLabelService::label('Texto que no matchea ningún marcador conocido.', 3)
        );
    }

    /**
     * El marcador manda sobre la posición: si por lo que sea el orden de armado
     * cambia, el rótulo sigue siendo correcto.
     */
    public function test_el_contenido_manda_sobre_la_posicion(): void
    {
        $this->assertSame(
            'Palabras prohibidas globales',
            PromptMessageLabelService::label('PALABRAS PROHIBIDAS GLOBALES: lista.', 5)
        );
    }
}
