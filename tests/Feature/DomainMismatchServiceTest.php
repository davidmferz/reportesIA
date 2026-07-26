<?php

namespace Tests\Feature;

use App\Models\Catalog\Sector;
use App\Services\DomainMismatchService;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsCatalogPaths;
use Tests\TestCase;

/**
 * El aviso de dominio compara SOLO los 4 niveles de jerarquía.
 * El tipo de servicio y el de documento quedan fuera: cambiar de entregable
 * es legítimo, cambiar de dominio es la causa raíz documentada de las alucinaciones.
 */
class DomainMismatchServiceTest extends TestCase
{
    use BuildsCatalogPaths;
    use RefreshDatabase;

    private DomainMismatchService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
        $this->service = app(DomainMismatchService::class);
    }

    /**
     * Camino de dominio a partir de los nombres del catálogo.
     */
    private function dominio(string $sector, string $rama, string $subrama, ?string $especialidad = null): array
    {
        $s = Sector::where('nombre', $sector)->firstOrFail();
        $r = $s->branches()->where('nombre', $rama)->firstOrFail();
        $sb = $r->subbranches()->where('nombre', $subrama)->firstOrFail();
        $e = $especialidad
            ? $sb->specialties()->where('nombre', $especialidad)->firstOrFail()
            : $sb->specialties()->firstOrFail();

        return [
            'catalog_sector_id' => $s->id,
            'catalog_branch_id' => $r->id,
            'catalog_subbranch_id' => $sb->id,
            'catalog_specialty_id' => $e->id,
        ];
    }

    public function test_no_warning_when_both_domains_are_identical(): void
    {
        $dominio = $this->dominio('Primario', 'Recursos naturales', 'Pesca');

        $this->assertNull($this->service->between($dominio, $dominio));
    }

    public function test_flags_a_different_sector_as_high_severity(): void
    {
        $mismatch = $this->service->between(
            $this->dominio('Primario', 'Recursos naturales', 'Pesca'),
            $this->dominio('Secundario', 'Construcción', 'Inmobiliario'),
        );

        $this->assertSame('catalog_sector_id', $mismatch['nivel']);
        $this->assertSame('Sector', $mismatch['etiqueta']);
        $this->assertSame('alta', $mismatch['severidad']);
    }

    public function test_flags_a_different_branch_within_the_same_sector(): void
    {
        $mismatch = $this->service->between(
            $this->dominio('Primario', 'Recursos naturales', 'Pesca'),
            $this->dominio('Primario', 'Minería', 'Energéticos'),
        );

        $this->assertSame('catalog_branch_id', $mismatch['nivel']);
        $this->assertSame('alta', $mismatch['severidad']);
    }

    public function test_flags_a_different_subbranch_as_medium_severity(): void
    {
        $mismatch = $this->service->between(
            $this->dominio('Primario', 'Recursos naturales', 'Pesca'),
            $this->dominio('Primario', 'Recursos naturales', 'Agricultura'),
        );

        $this->assertSame('catalog_subbranch_id', $mismatch['nivel']);
        $this->assertSame('media', $mismatch['severidad']);
    }

    public function test_flags_a_different_specialty_as_low_severity(): void
    {
        $mismatch = $this->service->between(
            $this->dominio('Primario', 'Recursos naturales', 'Agricultura', 'Operación agrícola'),
            $this->dominio('Primario', 'Recursos naturales', 'Agricultura', 'Servicios para Horticultura'),
        );

        $this->assertSame('catalog_specialty_id', $mismatch['nivel']);
        $this->assertSame('baja', $mismatch['severidad']);
    }

    public function test_reports_the_highest_divergence_not_the_deepest(): void
    {
        // Difieren en TODOS los niveles: debe avisar del sector, no de la especialidad.
        $mismatch = $this->service->between(
            $this->dominio('Primario', 'Recursos naturales', 'Pesca'),
            $this->dominio('Secundario', 'Industria', 'Industria alimentaria'),
        );

        $this->assertSame('catalog_sector_id', $mismatch['nivel']);
    }

    public function test_stays_silent_when_the_report_type_has_no_domain(): void
    {
        $this->assertNull($this->service->between(
            [],
            $this->dominio('Primario', 'Recursos naturales', 'Pesca'),
        ));
    }

    public function test_stays_silent_when_the_generation_has_no_domain(): void
    {
        $this->assertNull($this->service->between(
            $this->dominio('Primario', 'Recursos naturales', 'Pesca'),
            [],
        ));
    }

    public function test_stays_silent_when_only_the_deeper_levels_are_missing(): void
    {
        // Mismo sector declarado, generación sin especificar más abajo:
        // es dato faltante, no un cambio de dominio.
        $declarado = $this->dominio('Primario', 'Recursos naturales', 'Pesca');
        $usado = ['catalog_sector_id' => $declarado['catalog_sector_id']];

        $this->assertNull($this->service->between($declarado, $usado));
    }

    public function test_carries_the_readable_names_of_both_sides(): void
    {
        $mismatch = $this->service->between(
            $this->dominio('Primario', 'Recursos naturales', 'Pesca'),
            $this->dominio('Secundario', 'Construcción', 'Inmobiliario'),
        );

        $this->assertSame('Primario', $mismatch['declarado']);
        $this->assertSame('Secundario', $mismatch['usado']);
    }
}
