<?php

namespace Tests\Feature;

use App\Models\Catalog\DocumentSection;
use App\Models\Catalog\DocumentType;
use App\Models\Catalog\ServiceType;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Estructura sugerida por combinación servicio+documento.
 * Fuente: hoja "Estructuras_proyecto" del Excel del generador.
 */
class CatalogStructureSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_every_section_of_the_catalog(): void
    {
        $this->seed(CatalogSeeder::class);

        $this->assertSame(1156, DocumentSection::count());
    }

    public function test_every_document_type_has_a_structure(): void
    {
        $this->seed(CatalogSeeder::class);

        $sinEstructura = DocumentType::doesntHave('sections')->pluck('nombre');

        $this->assertCount(0, $sinEstructura, 'Hay tipos de documento sin estructura: '.$sinEstructura->implode(', '));
    }

    public function test_seeds_the_reference_structure_in_order(): void
    {
        $this->seed(CatalogSeeder::class);

        $documentType = $this->documentType('Calidad de productos y servicios', 'Verificación');

        $this->assertSame([
            'Introducción',
            'Objetivo',
            'Capítulo 1',
            'Capítulo 2',
            'Capítulo 3',
            'Capítulo 4',
            'Capítulo 5',
            'Conclusión',
        ], $documentType->sections()->pluck('apartado')->all());

        $this->assertSame(
            'Alcance, periodo y criterios de verificación',
            $documentType->sections()->where('orden', 3)->value('contenido')
        );
    }

    public function test_structures_differ_between_document_types(): void
    {
        $this->seed(CatalogSeeder::class);

        $verificacion = $this->documentType('Calidad de productos y servicios', 'Verificación');
        $plan = $this->documentType('Gestión ambiental', 'Plan');

        $this->assertSame(8, $verificacion->sections()->count());
        $this->assertSame(11, $plan->sections()->count());
        $this->assertContains('4.1', $plan->sections()->pluck('apartado')->all());
    }

    public function test_seeds_the_suggested_configuration_of_each_document_type(): void
    {
        $this->seed(CatalogSeeder::class);

        $documentType = $this->documentType('Calidad de productos y servicios', 'Verificación');

        $this->assertSame('2 a 4', $documentType->indicadores_sugeridos);
        $this->assertSame('Sí', $documentType->requiere_tablas);
        $this->assertSame('Sí', $documentType->requiere_formatos);
        $this->assertSame('No', $documentType->requiere_diagrama);
        $this->assertSame('Documento de verificación', $documentType->clasificacion_documental);
    }

    public function test_running_the_seeder_twice_does_not_duplicate_sections(): void
    {
        $this->seed(CatalogSeeder::class);
        $this->seed(CatalogSeeder::class);

        $this->assertSame(1156, DocumentSection::count());
    }

    private function documentType(string $servicio, string $documento): DocumentType
    {
        return ServiceType::where('nombre', $servicio)
            ->firstOrFail()
            ->documentTypes()
            ->where('nombre', $documento)
            ->firstOrFail();
    }
}
