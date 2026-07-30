<?php

namespace Tests\Feature;

use App\Models\AITraining;
use App\Models\Chapter;
use App\Models\ReportType;
use App\Models\User;
use App\Services\AITrainingService;
use App\Services\DocumentExtractorService;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use OpenAI\Laravel\Facades\OpenAI;
use OpenAI\Responses\Chat\CreateResponse;
use Tests\Feature\Concerns\BuildsCatalogPaths;
use Tests\TestCase;

/**
 * La clasificación elegida AL GENERAR (no la del tipo de reporte) es la que viaja
 * al prompt. Ese matiz es el punto de todo el asunto: el selector de la pantalla
 * de generación se precarga con la del tipo pero se puede ajustar, y ajustarla
 * tiene que cambiar lo que ve el modelo.
 */
class GenerationCatalogPromptTest extends TestCase
{
    use BuildsCatalogPaths;
    use RefreshDatabase;

    private ReportType $reportType;
    private Chapter $chapter;
    private AITraining $training;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CatalogSeeder::class);
        Storage::fake('local');

        $this->reportType = ReportType::create(['nombre' => 'Reporte pesquero']);
        $this->chapter = Chapter::create([
            'report_type_id' => $this->reportType->id,
            'nombre' => 'Capítulo 1',
            'orden' => 1,
        ]);
        $this->training = AITraining::create([
            'report_type_id' => $this->reportType->id,
            'status' => 'ready',
            'system_prompt' => 'Prompt',
            'examples_count' => 1,
        ]);
    }

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function payload(array $extra = []): array
    {
        return [
            'chapter_id' => $this->chapter->id,
            'archivos' => [UploadedFile::fake()->create('entrada.txt', 10)],
        ] + $extra;
    }

    /**
     * Captura la selección con la que el controlador invoca al servicio.
     */
    private function capturarSeleccion(array &$capturada): void
    {
        $this->mock(AITrainingService::class, function (MockInterface $mock) use (&$capturada) {
            $mock->shouldReceive('checkOpenAIStatus')->andReturn(['ok' => true, 'message' => 'OK']);
            $mock->shouldReceive('generateOutput')
                ->andReturnUsing(function ($training, $inputFiles, $model = null, array $catalogSelection = []) use (&$capturada) {
                    $capturada = $catalogSelection;

                    return [
                        'success' => true,
                        'content' => 'Contenido generado',
                        'usage' => [],
                        'validation' => ['valid' => true],
                    ];
                });
        });

        $this->mock(DocumentExtractorService::class, function (MockInterface $mock) {
            $mock->shouldReceive('extractText')->andReturn('texto extraído');
        });
    }

    public function test_la_clasificacion_de_la_generacion_llega_al_servicio(): void
    {
        $capturada = [];
        $this->capturarSeleccion($capturada);

        // El tipo declara pesca; la generación se hace para construcción.
        $this->reportType->update($this->coherentPath());
        $usado = $this->otherCoherentPath();

        $this->actingAs($this->admin())
            ->post(route('admin.ai-training.generate.store', $this->reportType), $this->payload($usado))
            ->assertRedirect();

        $this->assertSame(
            $usado['catalog_specialty_id'],
            $capturada['catalog_specialty_id'] ?? null,
            'El servicio recibió la clasificación del tipo, no la elegida al generar.'
        );
        $this->assertSame($usado['catalog_document_type_id'], $capturada['catalog_document_type_id'] ?? null);
    }

    public function test_sin_clasificacion_el_servicio_recibe_la_seleccion_vacia(): void
    {
        $capturada = ['centinela' => true];
        $this->capturarSeleccion($capturada);

        $this->actingAs($this->admin())
            ->post(route('admin.ai-training.generate.store', $this->reportType), $this->payload())
            ->assertRedirect();

        $this->assertArrayNotHasKey('centinela', $capturada);
        $this->assertNull($capturada['catalog_sector_id']);
    }

    public function test_el_prompt_enviado_a_openai_incluye_el_encuadre_de_clasificacion(): void
    {
        OpenAI::fake([
            CreateResponse::fake(['choices' => [['message' => ['content' => 'Documento generado.']]]]),
        ]);

        $service = app(AITrainingService::class);

        $result = $service->generateOutput(
            $this->training,
            [],
            'gpt-4o-mini',
            $this->coherentPath()
        );

        $systemContents = collect($result['prompt_messages'] ?? [])
            ->where('role', 'system')
            ->pluck('content')
            ->implode("\n");

        $this->assertStringContainsString('CLASIFICACIÓN DEL PROYECTO', $systemContents);
        $this->assertStringContainsString('Pesca', $systemContents);
    }

    public function test_sin_clasificacion_no_se_agrega_ningun_mensaje_extra(): void
    {
        OpenAI::fake([
            CreateResponse::fake(['choices' => [['message' => ['content' => 'Documento generado.']]]]),
        ]);

        $service = app(AITrainingService::class);

        $result = $service->generateOutput($this->training, [], 'gpt-4o-mini');

        $systemContents = collect($result['prompt_messages'] ?? [])
            ->where('role', 'system')
            ->pluck('content')
            ->implode("\n");

        $this->assertStringNotContainsString('CLASIFICACIÓN DEL PROYECTO', $systemContents);
    }

    /**
     * La pantalla declaraba que la orientación del catálogo "no altera la salida de
     * la IA". Desde que el encuadre entra al prompt, eso es falso — y una etiqueta
     * que miente sobre lo que hace el sistema es peor que no tenerla.
     */
    public function test_la_pantalla_ya_no_declara_que_el_catalogo_no_altera_la_salida(): void
    {
        $this->reportType->update($this->coherentPath());

        $this->actingAs($this->admin())
            ->get(route('admin.ai-training.generate.create', $this->reportType))
            ->assertOk()
            ->assertDontSee('no altera la salida de la IA', false);
    }

    public function test_la_pantalla_sirve_la_configuracion_sugerida_dentro_del_arbol(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.ai-training.generate.create', $this->reportType))
            ->assertOk()
            // Va en el JSON del árbol para que la pantalla reaccione al tipo de
            // documento elegido, en vez de quedarse con el del tipo de reporte.
            ->assertSee('Indicadores sugeridos', false)
            ->assertSee('¿Requiere tablas?', false);
    }

    public function test_el_prompt_maestro_sigue_intacto_con_clasificacion(): void
    {
        OpenAI::fake([
            CreateResponse::fake(['choices' => [['message' => ['content' => 'Documento generado.']]]]),
        ]);

        $service = app(AITrainingService::class);

        $result = $service->generateOutput($this->training, [], 'gpt-4o-mini', $this->coherentPath());

        // El maestro es verbatim: la clasificación va en su PROPIO mensaje, jamás
        // interpolada adentro del prompt canónico.
        $maestro = collect($result['prompt_messages'] ?? [])
            ->firstWhere('role', 'system')['content'] ?? '';

        $this->assertStringContainsString('consultor experto', $maestro);
        $this->assertStringNotContainsString('CLASIFICACIÓN DEL PROYECTO', $maestro);
    }
}
