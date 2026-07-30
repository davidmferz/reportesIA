<?php

namespace Tests\Feature;

use App\Models\AITraining;
use App\Models\AITrainingExample;
use App\Models\Chapter;
use App\Models\ReportType;
use App\Services\AITrainingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Al generar, elegir capítulo es obligatorio — pero selectBestExamples() no lo
 * miraba ("Por ahora, seleccionar los más recientes que quepan"). Resultado:
 * pedías el capítulo 2 y el few-shot te mostraba ejemplos de los otros, así que
 * el modelo aprendía la estructura equivocada.
 *
 * El filtro es PREFERENCIA, no exclusión. Si ninguno coincide y filtráramos
 * duro, el modelo se quedaría sin caso de referencia — y sin él la Fase 1 del
 * Prompt Maestro ("aprendé del ejemplo") se queda sin piso y la salida empeora
 * muchísimo. Por eso siempre hay fallback al resto.
 */
class ExampleSelectionByChapterTest extends TestCase
{
    use RefreshDatabase;

    private ReportType $reportType;
    private AITraining $training;
    private Chapter $mercado;
    private Chapter $conclusiones;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reportType = ReportType::create(['nombre' => 'Acciones de Posicionamiento']);
        $this->mercado = Chapter::create([
            'report_type_id' => $this->reportType->id,
            'nombre' => 'Análisis de Mercado',
            'orden' => 1,
        ]);
        $this->conclusiones = Chapter::create([
            'report_type_id' => $this->reportType->id,
            'nombre' => 'Conclusiones',
            'orden' => 2,
        ]);
        $this->training = AITraining::create([
            'report_type_id' => $this->reportType->id,
            'status' => 'ready',
            'system_prompt' => 'Prompt',
            'examples_count' => 0,
        ]);
    }

    private function ejemplo(?Chapter $capitulo, string $marca): AITrainingExample
    {
        return AITrainingExample::create([
            'ai_training_id' => $this->training->id,
            'grupo_id' => (string) Str::uuid(),
            'chapter_id' => $capitulo?->id,
            'capitulo' => $capitulo?->nombre,
            'input_content' => "entrada {$marca}",
            'output_content' => "salida {$marca}",
            'input_files_count' => 1,
            'processed_at' => now(),
        ]);
    }

    /**
     * @return list<string>
     */
    private function seleccionar(?int $chapterId, int $maxTokens = 100000): array
    {
        $service = app(AITrainingService::class);
        $metodo = new ReflectionMethod($service, 'selectBestExamples');

        $seleccion = $metodo->invoke($service, $this->training, 'nueva entrada', $maxTokens, ['min' => null, 'max' => null], $chapterId);

        return array_column($seleccion, 'output');
    }

    public function test_los_ejemplos_del_capitulo_pedido_van_primero(): void
    {
        $this->ejemplo($this->mercado, 'mercado-1');
        $this->ejemplo($this->conclusiones, 'conclusiones-1');
        $this->ejemplo($this->mercado, 'mercado-2');

        $salidas = $this->seleccionar($this->conclusiones->id);

        $this->assertSame('salida conclusiones-1', $salidas[0]);
    }

    public function test_los_demas_capitulos_siguen_disponibles_como_relleno(): void
    {
        $this->ejemplo($this->mercado, 'mercado-1');
        $this->ejemplo($this->conclusiones, 'conclusiones-1');

        $salidas = $this->seleccionar($this->conclusiones->id);

        $this->assertCount(2, $salidas, 'El filtro es preferencia, no exclusión.');
        $this->assertContains('salida mercado-1', $salidas);
    }

    public function test_si_ningun_ejemplo_coincide_igual_devuelve_ejemplos(): void
    {
        $this->ejemplo($this->mercado, 'mercado-1');
        $this->ejemplo($this->mercado, 'mercado-2');

        $salidas = $this->seleccionar($this->conclusiones->id);

        // Sin fallback el modelo se quedaría sin caso de referencia y la Fase 1
        // del maestro perdería el piso.
        $this->assertCount(2, $salidas);
    }

    public function test_cuando_el_presupuesto_solo_alcanza_para_uno_gana_el_del_capitulo(): void
    {
        $this->ejemplo($this->mercado, 'mercado-1');
        $this->ejemplo($this->conclusiones, 'conclusiones-1');

        // Presupuesto mínimo: entra un solo ejemplo.
        $salidas = $this->seleccionar($this->conclusiones->id, 1);

        $this->assertCount(1, $salidas);
        $this->assertSame('salida conclusiones-1', $salidas[0]);
    }

    /**
     * Sin capítulo pedido manda la recencia. Y sobre todo: manda un orden
     * DETERMINÍSTICO. La query no tenía `orderBy` — decía en un comentario que
     * elegía "los más recientes" pero dejaba el orden a criterio de la base, así
     * que con presupuesto ajustado la misma entrada podía entrenar con ejemplos
     * distintos en dos generaciones seguidas.
     */
    public function test_sin_capitulo_pedido_manda_el_mas_reciente(): void
    {
        $this->ejemplo($this->mercado, 'mercado-1');
        $this->ejemplo($this->conclusiones, 'conclusiones-1');

        $this->assertSame(
            ['salida conclusiones-1', 'salida mercado-1'],
            $this->seleccionar(null)
        );
    }

    public function test_el_orden_es_estable_entre_llamadas(): void
    {
        $this->ejemplo($this->mercado, 'mercado-1');
        $this->ejemplo($this->conclusiones, 'conclusiones-1');
        $this->ejemplo($this->mercado, 'mercado-2');

        $primera = $this->seleccionar($this->conclusiones->id);

        for ($i = 0; $i < 5; $i++) {
            $this->assertSame($primera, $this->seleccionar($this->conclusiones->id));
        }
    }

    public function test_los_ejemplos_sin_capitulo_no_rompen_la_preferencia(): void
    {
        $this->ejemplo(null, 'huerfano');
        $this->ejemplo($this->conclusiones, 'conclusiones-1');

        $salidas = $this->seleccionar($this->conclusiones->id);

        $this->assertSame('salida conclusiones-1', $salidas[0]);
        $this->assertContains('salida huerfano', $salidas);
    }
}
