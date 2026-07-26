<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\ReportType;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsCatalogPaths;
use Tests\TestCase;

class ChapterStructureScreenTest extends TestCase
{
    use BuildsCatalogPaths;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function clasificado(): ReportType
    {
        return ReportType::create(['nombre' => 'Reporte pesquero'] + $this->coherentPath());
    }

    public function test_chapters_screen_offers_to_load_the_catalog_structure(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.chapters.index', $this->clasificado()))
            ->assertOk()
            ->assertSee('estructura sugerida', false)
            ->assertSee('Calidad de productos y servicios &gt; Verificación', false);
    }

    public function test_chapters_screen_does_not_offer_it_without_a_document_type(): void
    {
        $sinClasificar = ReportType::create(['nombre' => 'Sin clasificar']);

        $this->actingAs($this->admin())
            ->get(route('admin.chapters.index', $sinClasificar))
            ->assertOk()
            ->assertDontSee('estructura sugerida', false);
    }

    public function test_loading_the_structure_creates_the_chapters(): void
    {
        $reportType = $this->clasificado();

        $this->actingAs($this->admin())
            ->post(route('admin.chapters.apply-structure', $reportType))
            ->assertRedirect(route('admin.chapters.index', $reportType))
            ->assertSessionHas('success');

        $this->assertSame(8, $reportType->chapters()->count());
    }

    public function test_loading_the_structure_does_not_wipe_existing_chapters_by_default(): void
    {
        $reportType = $this->clasificado();
        Chapter::create(['report_type_id' => $reportType->id, 'nombre' => 'Mi capítulo', 'orden' => 1]);

        $this->actingAs($this->admin())
            ->post(route('admin.chapters.apply-structure', $reportType))
            ->assertRedirect(route('admin.chapters.index', $reportType))
            ->assertSessionHas('error');

        $this->assertSame(['Mi capítulo'], $reportType->chapters()->pluck('nombre')->all());
    }

    public function test_loading_the_structure_replaces_when_explicitly_confirmed(): void
    {
        $reportType = $this->clasificado();
        Chapter::create(['report_type_id' => $reportType->id, 'nombre' => 'Mi capítulo', 'orden' => 1]);

        $this->actingAs($this->admin())
            ->post(route('admin.chapters.apply-structure', $reportType), ['replace' => '1'])
            ->assertRedirect(route('admin.chapters.index', $reportType))
            ->assertSessionHas('success');

        $this->assertSame(8, $reportType->chapters()->count());
        $this->assertNotContains('Mi capítulo', $reportType->chapters()->pluck('nombre')->all());
    }

    public function test_a_non_admin_cannot_load_the_structure(): void
    {
        $reportType = $this->clasificado();
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->post(route('admin.chapters.apply-structure', $reportType))
            ->assertForbidden();

        $this->assertSame(0, $reportType->chapters()->count());
    }

    public function test_generation_screen_shows_the_suggested_configuration(): void
    {
        $reportType = $this->clasificado();
        \App\Models\AITraining::create([
            'report_type_id' => $reportType->id,
            'status' => 'ready',
            'system_prompt' => 'Prompt',
            'examples_count' => 1,
        ]);

        $this->mock(\App\Services\AITrainingService::class, function ($mock) {
            $mock->shouldReceive('checkOpenAIStatus')->andReturn(['ok' => true, 'message' => 'OK']);
        });

        $this->actingAs($this->admin())
            ->get(route('admin.ai-training.generate.create', $reportType))
            ->assertOk()
            ->assertSee('Documento de verificación')
            ->assertSee('2 a 4');
    }
}
