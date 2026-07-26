<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\ReportType;
use App\Models\User;
use App\Services\ChapterStructureService;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\Feature\Concerns\BuildsCatalogPaths;
use Tests\TestCase;

class ChapterStructureServiceTest extends TestCase
{
    use BuildsCatalogPaths;
    use RefreshDatabase;

    private ChapterStructureService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
        $this->service = app(ChapterStructureService::class);
    }

    private function reportTypeConEstructura(): ReportType
    {
        // coherentPath() apunta a Calidad de productos y servicios ¦ Verificación.
        return ReportType::create(['nombre' => 'Reporte pesquero'] + $this->coherentPath());
    }

    public function test_creates_the_chapters_of_the_catalog_structure_in_order(): void
    {
        $reportType = $this->reportTypeConEstructura();

        $creados = $this->service->applyTo($reportType);

        $this->assertSame(8, $creados);
        $this->assertSame([
            'Introducción',
            'Objetivo',
            'Capítulo 1',
            'Capítulo 2',
            'Capítulo 3',
            'Capítulo 4',
            'Capítulo 5',
            'Conclusión',
        ], $reportType->chapters()->pluck('nombre')->all());
    }

    public function test_carries_the_suggested_content_into_the_chapter_description(): void
    {
        $reportType = $this->reportTypeConEstructura();

        $this->service->applyTo($reportType);

        $this->assertSame(
            'Alcance, periodo y criterios de verificación',
            $reportType->chapters()->where('orden', 3)->value('descripcion')
        );
    }

    public function test_stamps_the_acting_user_as_author(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);
        $reportType = $this->reportTypeConEstructura();

        $this->service->applyTo($reportType);

        $this->assertSame($user->id, $reportType->chapters()->first()->created_by);
    }

    public function test_refuses_when_the_report_type_has_no_document_type(): void
    {
        $reportType = ReportType::create(['nombre' => 'Sin clasificar']);

        $this->expectException(RuntimeException::class);

        $this->service->applyTo($reportType);
    }

    public function test_refuses_to_silently_overwrite_existing_chapters(): void
    {
        $reportType = $this->reportTypeConEstructura();
        Chapter::create(['report_type_id' => $reportType->id, 'nombre' => 'Mi capítulo', 'orden' => 1]);

        try {
            $this->service->applyTo($reportType);
            $this->fail('Se esperaba que rechazara sobrescribir capítulos existentes.');
        } catch (RuntimeException $e) {
            // El capítulo del usuario sigue intacto.
            $this->assertSame(['Mi capítulo'], $reportType->chapters()->pluck('nombre')->all());
        }
    }

    public function test_replaces_existing_chapters_only_when_asked_and_keeps_them_recoverable(): void
    {
        $reportType = $this->reportTypeConEstructura();
        $previo = Chapter::create(['report_type_id' => $reportType->id, 'nombre' => 'Mi capítulo', 'orden' => 1]);

        $creados = $this->service->applyTo($reportType, replace: true);

        $this->assertSame(8, $creados);
        $this->assertNotContains('Mi capítulo', $reportType->chapters()->pluck('nombre')->all());
        $this->assertSoftDeleted($previo);
    }

    public function test_preview_returns_the_structure_without_touching_the_database(): void
    {
        $reportType = $this->reportTypeConEstructura();

        $preview = $this->service->previewFor($reportType);

        $this->assertCount(8, $preview);
        $this->assertSame('Introducción', $preview[0]['apartado']);
        $this->assertSame(0, $reportType->chapters()->count());
    }
}
