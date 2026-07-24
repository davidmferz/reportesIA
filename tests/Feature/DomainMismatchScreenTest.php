<?php

namespace Tests\Feature;

use App\Models\AIGeneration;
use App\Models\AITraining;
use App\Models\Chapter;
use App\Models\ReportType;
use App\Models\User;
use App\Services\AITrainingService;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsCatalogPaths;
use Tests\TestCase;

class DomainMismatchScreenTest extends TestCase
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

        // El tipo de reporte declara el dominio pesquero.
        $this->reportType = ReportType::create(['nombre' => 'Reporte pesquero'] + $this->coherentPath());
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

        $this->mock(AITrainingService::class, function ($mock) {
            $mock->shouldReceive('checkOpenAIStatus')->andReturn(['ok' => true, 'message' => 'OK']);
        });
    }

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function generation(User $admin, array $catalog): AIGeneration
    {
        return AIGeneration::create([
            'ai_training_id' => $this->training->id,
            'user_id' => $admin->id,
            'chapter_id' => $this->chapter->id,
            'titulo' => 'Generación',
            'input_content' => 'input',
            'output_content' => 'output',
            'status' => 'completed',
            'generated_at' => now(),
        ] + $catalog);
    }

    public function test_generate_screen_ships_the_declared_domain_as_baseline(): void
    {
        $path = $this->coherentPath();

        $this->actingAs($this->admin())
            ->get(route('admin.ai-training.generate.create', $this->reportType))
            ->assertOk()
            ->assertSee('"catalog_sector_id":'.$path['catalog_sector_id'], false)
            ->assertSee('dominioDeclarado', false);
    }

    public function test_generation_detail_warns_when_the_domain_differs(): void
    {
        $admin = $this->admin();
        // Generada bajo Secundario > Construcción, pero el tipo declara Primario > Pesca.
        $generation = $this->generation($admin, $this->otherCoherentPath());

        $this->actingAs($admin)
            ->get(route('admin.ai-training.generation.show', [$this->reportType, $generation]))
            ->assertOk()
            ->assertSee('dominio')
            ->assertSee('Primario')
            ->assertSee('Secundario');
    }

    public function test_generation_detail_stays_quiet_when_the_domain_matches(): void
    {
        $admin = $this->admin();
        $generation = $this->generation($admin, $this->coherentPath());

        $this->actingAs($admin)
            ->get(route('admin.ai-training.generation.show', [$this->reportType, $generation]))
            ->assertOk()
            ->assertDontSee('Aviso de dominio');
    }

    public function test_generation_detail_stays_quiet_without_a_classification(): void
    {
        $admin = $this->admin();
        $generation = $this->generation($admin, []);

        $this->actingAs($admin)
            ->get(route('admin.ai-training.generation.show', [$this->reportType, $generation]))
            ->assertOk()
            ->assertDontSee('Aviso de dominio');
    }

    public function test_the_warning_never_blocks_generating(): void
    {
        // El aviso es informativo: una generación con dominio distinto se guarda igual.
        $admin = $this->admin();
        $generation = $this->generation($admin, $this->otherCoherentPath());

        $this->assertSame('completed', $generation->status);
        $this->assertDatabaseHas('ai_generations', ['id' => $generation->id]);
    }
}
