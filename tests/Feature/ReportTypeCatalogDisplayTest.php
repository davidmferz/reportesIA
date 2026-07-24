<?php

namespace Tests\Feature;

use App\Models\ReportType;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\BuildsCatalogPaths;
use Tests\TestCase;

class ReportTypeCatalogDisplayTest extends TestCase
{
    use BuildsCatalogPaths;
    use RefreshDatabase;

    private const RUTA = 'Primario > Recursos naturales > Pesca > Conservación y explotación pesquera y acuicultura';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    public function test_show_displays_the_full_catalog_path(): void
    {
        $reportType = ReportType::create(['nombre' => 'Reporte pesquero'] + $this->coherentPath());

        $this->actingAs($this->admin())
            ->get(route('admin.report-types.show', $reportType))
            ->assertOk()
            ->assertSee(self::RUTA)
            ->assertSee('Calidad de productos y servicios > Verificación');
    }

    public function test_show_reports_an_unclassified_report_type_instead_of_rendering_an_empty_row(): void
    {
        $reportType = ReportType::create(['nombre' => 'Sin catálogo']);

        $this->actingAs($this->admin())
            ->get(route('admin.report-types.show', $reportType))
            ->assertOk()
            ->assertSee('Sin clasificar');
    }

    public function test_index_displays_the_catalog_path_of_each_report_type(): void
    {
        ReportType::create(['nombre' => 'Reporte pesquero'] + $this->coherentPath());

        $this->actingAs($this->admin())
            ->get(route('admin.report-types.index'))
            ->assertOk()
            ->assertSee(self::RUTA);
    }

    public function test_index_survives_report_types_without_a_classification(): void
    {
        ReportType::create(['nombre' => 'Sin catálogo']);

        $this->actingAs($this->admin())
            ->get(route('admin.report-types.index'))
            ->assertOk()
            ->assertSee('Sin catálogo');
    }

    public function test_index_does_not_run_extra_queries_per_report_type(): void
    {
        $admin = $this->admin();

        ReportType::create(['nombre' => 'Uno'] + $this->coherentPath());
        $conUno = $this->countIndexQueries($admin);

        ReportType::create(['nombre' => 'Dos'] + $this->otherCoherentPath());
        ReportType::create(['nombre' => 'Tres'] + $this->coherentPath());
        $conTres = $this->countIndexQueries($admin);

        $this->assertSame(
            $conUno,
            $conTres,
            'El listado dispara consultas por fila: falta eager loading del catálogo.'
        );
    }

    private function countIndexQueries(User $admin): int
    {
        $queries = 0;

        DB::flushQueryLog();
        DB::listen(function () use (&$queries) {
            $queries++;
        });

        $this->actingAs($admin)->get(route('admin.report-types.index'))->assertOk();

        return $queries;
    }
}
