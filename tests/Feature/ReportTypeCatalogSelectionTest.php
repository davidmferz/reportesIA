<?php

namespace Tests\Feature;

use App\Models\ReportType;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsCatalogPaths;
use Tests\TestCase;

class ReportTypeCatalogSelectionTest extends TestCase
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

    public function test_create_screen_renders_the_catalog_selector(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.report-types.create'))
            ->assertOk()
            ->assertSee('catalog_sector_id')
            ->assertSee('Recursos naturales', false);
    }

    public function test_store_persists_the_whole_selection(): void
    {
        $path = $this->coherentPath();

        $this->actingAs($this->admin())
            ->post(route('admin.report-types.store'), ['nombre' => 'Reporte pesquero'] + $path)
            ->assertRedirect(route('admin.report-types.index'));

        $reportType = ReportType::where('nombre', 'Reporte pesquero')->firstOrFail();

        foreach ($path as $column => $id) {
            $this->assertSame($id, $reportType->{$column}, "No se guardó {$column}.");
        }
    }

    public function test_the_selection_stays_optional(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.report-types.store'), ['nombre' => 'Sin catálogo'])
            ->assertRedirect(route('admin.report-types.index'));

        $this->assertDatabaseHas('report_types', [
            'nombre' => 'Sin catálogo',
            'catalog_sector_id' => null,
            'catalog_document_type_id' => null,
        ]);
    }

    public static function incoherentSelectionProvider(): array
    {
        return [
            'rama de otro sector' => ['catalog_branch_id', 'foreignBranchId'],
            'subrama de otra rama' => ['catalog_subbranch_id', 'foreignSubbranchId'],
            'especialidad de otra subrama' => ['catalog_specialty_id', 'foreignSpecialtyId'],
            'documento de otro servicio' => ['catalog_document_type_id', 'foreignDocumentTypeId'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('incoherentSelectionProvider')]
    public function test_store_rejects_a_child_that_does_not_belong_to_its_parent(string $field, string $foreignIdFactory): void
    {
        $path = $this->coherentPath();
        $path[$field] = $this->{$foreignIdFactory}();

        $this->actingAs($this->admin())
            ->post(route('admin.report-types.store'), ['nombre' => 'Incoherente'] + $path)
            ->assertSessionHasErrors($field);

        $this->assertDatabaseMissing('report_types', ['nombre' => 'Incoherente']);
    }

    public function test_store_rejects_a_child_sent_without_its_parent(): void
    {
        $path = $this->coherentPath();
        $path['catalog_sector_id'] = null;

        $this->actingAs($this->admin())
            ->post(route('admin.report-types.store'), ['nombre' => 'Huérfano'] + $path)
            ->assertSessionHasErrors('catalog_branch_id');

        $this->assertDatabaseMissing('report_types', ['nombre' => 'Huérfano']);
    }

    public function test_edit_screen_preselects_the_saved_path(): void
    {
        $path = $this->coherentPath();
        $reportType = ReportType::create(['nombre' => 'Reporte pesquero'] + $path);

        $this->actingAs($this->admin())
            ->get(route('admin.report-types.edit', $reportType))
            ->assertOk()
            ->assertSee('name="catalog_sector_id"', false)
            ->assertSee('"catalog_specialty_id":'.$path['catalog_specialty_id'], false)
            ->assertSee('"catalog_document_type_id":'.$path['catalog_document_type_id'], false);
    }

    public function test_update_replaces_the_previous_selection(): void
    {
        $reportType = ReportType::create(['nombre' => 'Reporte pesquero'] + $this->coherentPath());
        $nuevo = $this->otherCoherentPath();

        $this->actingAs($this->admin())
            ->put(route('admin.report-types.update', $reportType), ['nombre' => 'Reporte pesquero'] + $nuevo)
            ->assertRedirect(route('admin.report-types.index'));

        $reportType->refresh();

        foreach ($nuevo as $column => $id) {
            $this->assertSame($id, $reportType->{$column}, "No se actualizó {$column}.");
        }
    }

    public function test_update_can_clear_the_selection(): void
    {
        $reportType = ReportType::create(['nombre' => 'Reporte pesquero'] + $this->coherentPath());

        $this->actingAs($this->admin())
            ->put(route('admin.report-types.update', $reportType), ['nombre' => 'Reporte pesquero'])
            ->assertRedirect(route('admin.report-types.index'));

        $reportType->refresh();

        $this->assertNull($reportType->catalog_sector_id);
        $this->assertNull($reportType->catalog_document_type_id);
    }

    public function test_report_type_exposes_the_selection_as_relations(): void
    {
        $reportType = ReportType::create(['nombre' => 'Reporte pesquero'] + $this->coherentPath());

        $this->assertSame('Primario', $reportType->catalogSector->nombre);
        $this->assertSame('Recursos naturales', $reportType->catalogBranch->nombre);
        $this->assertSame('Pesca', $reportType->catalogSubbranch->nombre);
        $this->assertSame('Conservación y explotación pesquera y acuicultura', $reportType->catalogSpecialty->nombre);
        $this->assertSame('Calidad de productos y servicios', $reportType->catalogServiceType->nombre);
        $this->assertSame('Verificación', $reportType->catalogDocumentType->nombre);
    }
}
