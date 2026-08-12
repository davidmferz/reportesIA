<?php

namespace Tests\Feature;

use App\Services\OutputValidatorService;
use App\Services\PromptParserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cuando "Aportar conocimiento del modelo" está encendido, el documento incorpora
 * marco técnico del sector que NO viene de los archivos del cliente. La prueba de
 * contraste mostró que el modelo lo declara por su cuenta ("los umbrales son
 * ejemplos técnicos de referencia", "no incorporan mediciones específicas del
 * cliente") — pero lo hace por criterio propio, no porque el sistema lo exija, y
 * eso no garantiza que ocurra en cada generación.
 *
 * El riesgo concreto: un entregable que el cliente le factura a un tercero, con un
 * valor de referencia leído como dato verificado. Por eso la declaración se
 * verifica en vez de confiarse.
 *
 * Severidad CRITICAL a propósito, y es la excepción a la regla de los avisos del
 * catálogo (que son warning para no forzar estructura contra el Prompt Maestro):
 * acá el reintento pide AGREGAR una declaración, no reescribir el análisis. Es
 * aditivo, no distorsiona el contenido, y el loop está acotado por
 * maxValidationRetries.
 *
 * Solo aplica con el permiso encendido: apagado, la salida debería salir
 * exclusivamente de la entrada y no hay nada que declarar.
 */
class EstimationDisclosureTest extends TestCase
{
    // El validador consulta las palabras prohibidas globales (tabla forbidden_words).
    use RefreshDatabase;

    private function validator(): OutputValidatorService
    {
        return new OutputValidatorService(new PromptParserService());
    }

    private function tipos(array $violations): array
    {
        return array_column($violations, 'type');
    }

    private function violacion(array $resultado): ?array
    {
        foreach ($resultado['violations'] as $v) {
            if ($v['type'] === 'missing_estimation_disclosure') {
                return $v;
            }
        }

        return null;
    }

    public function test_exige_la_declaracion_cuando_el_permiso_esta_encendido(): void
    {
        $salida = "## Análisis\n\nLos tiempos de tránsito superan los 12 días y el umbral de alerta es 20%.";

        $resultado = $this->validator()->validate($salida, null, [], true);

        $violacion = $this->violacion($resultado);

        $this->assertNotNull($violacion, 'Con el permiso encendido y sin declaración debe haber violación.');
        $this->assertSame('critical', $violacion['severity']);
        $this->assertFalse($resultado['valid'], 'Un critical invalida la salida y dispara reintento.');
    }

    public function test_no_exige_nada_con_el_permiso_apagado(): void
    {
        $salida = "## Análisis\n\nLos tiempos de tránsito superan los 12 días y el umbral de alerta es 20%.";

        $resultado = $this->validator()->validate($salida, null, [], false);

        $this->assertNotContains('missing_estimation_disclosure', $this->tipos($resultado['violations']));
    }

    public function test_el_permiso_apagado_es_el_default(): void
    {
        $resultado = $this->validator()->validate('Un documento sin declaración.', null);

        $this->assertNotContains('missing_estimation_disclosure', $this->tipos($resultado['violations']));
    }

    /**
     * El vocabulario sale de lo que el modelo realmente escribió en la prueba de
     * contraste y de los términos que el cliente usó en su propio prompt
     * ("información proporcionada, derivada y estimada").
     *
     * @dataProvider declaracionesValidas
     */
    public function test_acepta_las_formas_reales_de_declarar(string $declaracion): void
    {
        $salida = "## Análisis\n\nEl umbral de alerta es 20%.\n\n{$declaracion}";

        $resultado = $this->validator()->validate($salida, null, [], true);

        $this->assertNull(
            $this->violacion($resultado),
            "Debería aceptar la declaración: \"{$declaracion}\""
        );
    }

    public static function declaracionesValidas(): array
    {
        return [
            'frase real de la prueba' => ['Los umbrales son ejemplos técnicos de referencia.'],
            'carácter general' => ['Las propuestas son de carácter general y no incorporan mediciones específicas del cliente.'],
            'estimación' => ['Los valores presentados son una estimación técnica.'],
            'estimados' => ['Los volúmenes indicados son estimados.'],
            'supuestos' => ['El cálculo parte de supuestos razonables sobre la operación.'],
            'orientativo' => ['El cuadro es orientativo y debe ajustarse con datos internos.'],
        ];
    }

    /**
     * La conexión es tan importante como la regla: el validador podía tener el
     * parámetro y nadie pasárselo, y toda la suite seguiría en verde mientras la
     * función no existe en producción. Esto blinda que generateOutput reenvíe el
     * permiso hasta el validador.
     *
     * Se apoya en que el juez de similitud devuelve `skipped_no_references` con una
     * colección vacía, así que la validación de similitud no interfiere.
     */
    public function test_la_generacion_reenvia_el_permiso_hasta_el_validador(): void
    {
        $service = new \App\Services\AITrainingService(
            new \App\Services\DocumentExtractorService(),
            $this->validator(),
        );

        $metodo = new \ReflectionMethod($service, 'validateGeneratedOutput');

        $conPermiso = $metodo->invoke(
            $service,
            'Un análisis sin ninguna declaración.',
            null,
            collect(),
            [],
            true
        );

        $sinPermiso = $metodo->invoke(
            $service,
            'Un análisis sin ninguna declaración.',
            null,
            collect(),
            [],
            false
        );

        $this->assertContains('missing_estimation_disclosure', $this->tipos($conPermiso['violations']));
        $this->assertNotContains('missing_estimation_disclosure', $this->tipos($sinPermiso['violations']));
    }

    public function test_el_feedback_le_dice_a_la_ia_exactamente_que_agregar(): void
    {
        $resultado = $this->validator()->validate('Un análisis sin declaración.', null, [], true);

        $feedback = $resultado['feedback_for_ai'];

        $this->assertNotNull($feedback);
        $this->assertStringContainsString('proporcionada', $feedback);
        $this->assertStringContainsString('estimada', $feedback);
        // El reintento tiene que ser aditivo: no puede pedir reescribir el análisis.
        $this->assertStringNotContainsString('Reescribí el análisis', $feedback);
    }
}
