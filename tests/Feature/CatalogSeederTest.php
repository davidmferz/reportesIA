<?php

namespace Tests\Feature;

use App\Models\Catalog\Branch;
use App\Models\Catalog\DocumentType;
use App\Models\Catalog\Sector;
use App\Models\Catalog\ServiceType;
use App\Models\Catalog\Specialty;
use App\Models\Catalog\Subbranch;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El catálogo proviene de docs/Generador_Proyectos_Anidado_Excel_2019.xlsx
 * (hojas "Clasificación" y "Listas_2019").
 */
class CatalogSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_the_four_level_hierarchy(): void
    {
        $this->seed(CatalogSeeder::class);

        $this->assertSame(4, Sector::count());
        $this->assertSame(19, Branch::count());
        $this->assertSame(48, Subbranch::count());
        $this->assertSame(107, Specialty::count());
    }

    public function test_seeds_service_types_with_their_document_types(): void
    {
        $this->seed(CatalogSeeder::class);

        $this->assertSame(12, ServiceType::count());
        $this->assertSame(114, DocumentType::count());
    }

    public function test_walks_the_reference_path_from_sector_down_to_specialty(): void
    {
        $this->seed(CatalogSeeder::class);

        $sector = Sector::where('nombre', 'Primario')->firstOrFail();
        $branch = $sector->branches()->where('nombre', 'Recursos naturales')->firstOrFail();
        $subbranch = $branch->subbranches()->where('nombre', 'Pesca')->firstOrFail();

        $this->assertSame(
            ['Conservación y explotación pesquera y acuicultura'],
            $subbranch->specialties()->pluck('nombre')->all()
        );
    }

    public function test_subbranches_never_leak_across_branches(): void
    {
        $this->seed(CatalogSeeder::class);

        $recursosNaturales = Branch::where('nombre', 'Recursos naturales')->firstOrFail();
        $mineria = Branch::where('nombre', 'Minería')->firstOrFail();

        $this->assertContains('Pesca', $recursosNaturales->subbranches()->pluck('nombre')->all());
        $this->assertNotContains('Pesca', $mineria->subbranches()->pluck('nombre')->all());
    }

    public function test_document_types_hang_from_their_own_service_type(): void
    {
        $this->seed(CatalogSeeder::class);

        // "Informe" solo existe bajo "Información y análisis estadístico".
        $estadistico = ServiceType::where('nombre', 'Información y análisis estadístico')->firstOrFail();
        $logistica = ServiceType::where('nombre', 'Logística y distribución')->firstOrFail();

        $this->assertContains('Informe', $estadistico->documentTypes()->pluck('nombre')->all());
        $this->assertNotContains('Informe', $logistica->documentTypes()->pluck('nombre')->all());
    }

    public function test_running_the_seeder_twice_does_not_duplicate_anything(): void
    {
        $this->seed(CatalogSeeder::class);
        $this->seed(CatalogSeeder::class);

        $this->assertSame(4, Sector::count());
        $this->assertSame(19, Branch::count());
        $this->assertSame(48, Subbranch::count());
        $this->assertSame(107, Specialty::count());
        $this->assertSame(12, ServiceType::count());
        $this->assertSame(114, DocumentType::count());
    }
}
