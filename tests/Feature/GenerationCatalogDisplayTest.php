<?php

namespace Tests\Feature;

use App\Models\AIGeneration;
use App\Models\AITraining;
use App\Models\Chapter;
use App\Models\ReportType;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\BuildsCatalogPaths;
use Tests\TestCase;

class GenerationCatalogDisplayTest extends TestCase
{
    use BuildsCatalogPaths;
    use RefreshDatabase;

    private const RUTA = 'Primario > Recursos naturales > Pesca > Conservación y explotación pesquera y acuicultura';

    private ReportType $reportType;
    private Chapter $chapter;
    private AITraining $training;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CatalogSeeder::class);

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

    private function generation(User $admin, array $catalog = [], string $titulo = 'Generación'): AIGeneration
    {
        return AIGeneration::create([
            'ai_training_id' => $this->training->id,
            'user_id' => $admin->id,
            'chapter_id' => $this->chapter->id,
            'titulo' => $titulo,
            'input_content' => 'input',
            'output_content' => 'output',
            'status' => 'completed',
            'generated_at' => now(),
        ] + $catalog);
    }

    public function test_generation_detail_displays_the_full_catalog_path(): void
    {
        $admin = $this->admin();
        $generation = $this->generation($admin, $this->coherentPath());

        $this->actingAs($admin)
            ->get(route('admin.ai-training.generation.show', [$this->reportType, $generation]))
            ->assertOk()
            ->assertSee(self::RUTA)
            ->assertSee('Calidad de productos y servicios > Verificación');
    }

    public function test_generation_detail_hides_the_block_when_there_is_no_classification(): void
    {
        $admin = $this->admin();
        $generation = $this->generation($admin);

        $this->actingAs($admin)
            ->get(route('admin.ai-training.generation.show', [$this->reportType, $generation]))
            ->assertOk()
            ->assertDontSee('Clasificación del proyecto');
    }

    public function test_generations_list_displays_the_catalog_path(): void
    {
        $admin = $this->admin();
        $this->generation($admin, $this->coherentPath());

        $this->actingAs($admin)
            ->get(route('admin.ai-training.generations', $this->reportType))
            ->assertOk()
            ->assertSee(self::RUTA);
    }

    public function test_generations_list_survives_generations_without_a_classification(): void
    {
        $admin = $this->admin();
        $this->generation($admin, [], 'Generación sin catálogo');

        $this->actingAs($admin)
            ->get(route('admin.ai-training.generations', $this->reportType))
            ->assertOk()
            ->assertSee('Generación sin catálogo');
    }

    public function test_generations_list_does_not_run_extra_queries_per_row(): void
    {
        $admin = $this->admin();

        $this->generation($admin, $this->coherentPath(), 'Uno');
        $conUna = $this->countListQueries($admin);

        $this->generation($admin, $this->otherCoherentPath(), 'Dos');
        $this->generation($admin, $this->coherentPath(), 'Tres');
        $conTres = $this->countListQueries($admin);

        $this->assertSame(
            $conUna,
            $conTres,
            'El listado de generaciones dispara consultas por fila: falta eager loading del catálogo.'
        );
    }

    private function countListQueries(User $admin): int
    {
        $queries = 0;

        DB::listen(function () use (&$queries) {
            $queries++;
        });

        $this->actingAs($admin)
            ->get(route('admin.ai-training.generations', $this->reportType))
            ->assertOk();

        return $queries;
    }
}
