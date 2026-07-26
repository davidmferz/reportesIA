<?php

namespace Tests\Feature;

use App\Models\Catalog\Sector;
use App\Services\CatalogService;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogServiceTest extends TestCase
{
    use RefreshDatabase;

    private CatalogService $catalog;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
        $this->catalog = app(CatalogService::class);
    }

    public function test_tree_nests_every_level_under_its_parent(): void
    {
        $tree = $this->catalog->tree();

        $sectores = collect($tree['sectors']);
        $primario = $sectores->firstWhere('nombre', 'Primario');
        $this->assertNotNull($primario, 'Falta el sector Primario en el árbol.');

        $recursos = collect($primario['branches'])->firstWhere('nombre', 'Recursos naturales');
        $this->assertNotNull($recursos, 'Falta la rama Recursos naturales.');

        $pesca = collect($recursos['subbranches'])->firstWhere('nombre', 'Pesca');
        $this->assertNotNull($pesca, 'Falta la subrama Pesca.');

        $this->assertSame(
            ['Conservación y explotación pesquera y acuicultura'],
            collect($pesca['specialties'])->pluck('nombre')->all()
        );
    }

    public function test_tree_carries_the_ids_the_form_will_submit(): void
    {
        $primario = collect($this->catalog->tree()['sectors'])->firstWhere('nombre', 'Primario');

        $this->assertSame(Sector::where('nombre', 'Primario')->value('id'), $primario['id']);
    }

    public function test_tree_nests_document_types_under_their_service_type(): void
    {
        $tree = $this->catalog->tree();

        $estadistico = collect($tree['service_types'])
            ->firstWhere('nombre', 'Información y análisis estadístico');

        $this->assertNotNull($estadistico);
        $this->assertContains('Informe', collect($estadistico['document_types'])->pluck('nombre')->all());
    }

    public function test_columns_lists_the_six_persisted_selection_fields(): void
    {
        $this->assertSame([
            'catalog_sector_id',
            'catalog_branch_id',
            'catalog_subbranch_id',
            'catalog_specialty_id',
            'catalog_service_type_id',
            'catalog_document_type_id',
        ], CatalogService::columns());
    }
}
