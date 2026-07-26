<?php

namespace Tests\Feature;

use App\Models\AIGeneration;
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
use Tests\Feature\Concerns\BuildsCatalogPaths;
use Tests\TestCase;

class GenerationCatalogSelectionTest extends TestCase
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

        $this->mock(AITrainingService::class, function (MockInterface $mock) {
            $mock->shouldReceive('checkOpenAIStatus')->andReturn(['ok' => true, 'message' => 'OK']);
            $mock->shouldReceive('generateOutput')->andReturn([
                'success' => true,
                'content' => 'Contenido generado',
                'usage' => [],
                'validation' => ['valid' => true],
            ]);
        });

        $this->mock(DocumentExtractorService::class, function (MockInterface $mock) {
            $mock->shouldReceive('extractText')->andReturn('texto extraído');
        });
    }

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function generatePayload(array $extra = []): array
    {
        return [
            'chapter_id' => $this->chapter->id,
            'archivos' => [UploadedFile::fake()->create('entrada.txt', 10)],
        ] + $extra;
    }

    public function test_generate_screen_renders_the_catalog_selector(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.ai-training.generate.create', $this->reportType))
            ->assertOk()
            ->assertSee('catalog_sector_id')
            ->assertSee('Recursos naturales', false);
    }

    public function test_generate_screen_preselects_the_report_type_classification(): void
    {
        $path = $this->coherentPath();
        $this->reportType->update($path);

        $this->actingAs($this->admin())
            ->get(route('admin.ai-training.generate.create', $this->reportType))
            ->assertOk()
            ->assertSee('"catalog_specialty_id":'.$path['catalog_specialty_id'], false)
            ->assertSee('"catalog_document_type_id":'.$path['catalog_document_type_id'], false);
    }

    public function test_generation_persists_the_selection(): void
    {
        $path = $this->coherentPath();

        $this->actingAs($this->admin())
            ->post(route('admin.ai-training.generate.store', $this->reportType), $this->generatePayload($path))
            ->assertRedirect();

        $generation = AIGeneration::latest('id')->firstOrFail();

        foreach ($path as $column => $id) {
            $this->assertSame($id, $generation->{$column}, "No se guardó {$column}.");
        }
    }

    public function test_the_selection_stays_optional(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.ai-training.generate.store', $this->reportType), $this->generatePayload())
            ->assertRedirect();

        $generation = AIGeneration::latest('id')->firstOrFail();

        $this->assertNull($generation->catalog_sector_id);
        $this->assertNull($generation->catalog_document_type_id);
    }

    public function test_generation_rejects_an_incoherent_selection_without_creating_a_record(): void
    {
        $path = $this->coherentPath();
        $path['catalog_subbranch_id'] = $this->foreignSubbranchId();

        $this->actingAs($this->admin())
            ->post(route('admin.ai-training.generate.store', $this->reportType), $this->generatePayload($path))
            ->assertSessionHasErrors('catalog_subbranch_id');

        $this->assertSame(0, AIGeneration::count());
    }

    public function test_generation_exposes_the_selection_as_relations(): void
    {
        $path = $this->coherentPath();

        $this->actingAs($this->admin())
            ->post(route('admin.ai-training.generate.store', $this->reportType), $this->generatePayload($path));

        $generation = AIGeneration::latest('id')->firstOrFail();

        $this->assertSame('Pesca', $generation->catalogSubbranch->nombre);
        $this->assertSame('Verificación', $generation->catalogDocumentType->nombre);
    }
}
