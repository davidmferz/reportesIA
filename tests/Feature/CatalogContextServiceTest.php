<?php

namespace Tests\Feature;

use App\Services\CatalogContextService;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsCatalogPaths;
use Tests\TestCase;

/**
 * La clasificación del proyecto deja de ser metadata muerta y entra al prompt
 * como ENCUADRE, en un mensaje de sistema propio — nunca dentro del Prompt
 * Maestro, que es verbatim por exigencia del cliente.
 *
 * El contrato clave: el encuadre da vocabulario y desambiguación, NO licencia
 * para aportar hechos. Esa distinción es exactamente la causa raíz documentada
 * de las "alucinaciones" (dominio declarado ≠ dominio del entrenamiento), así
 * que se blinda con tests.
 */
class CatalogContextServiceTest extends TestCase
{
    use BuildsCatalogPaths;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CatalogSeeder::class);
    }

    private function service(): CatalogContextService
    {
        return app(CatalogContextService::class);
    }

    public function test_sin_clasificacion_no_hay_mensaje(): void
    {
        $this->assertNull($this->service()->promptMessage([]));
        $this->assertNull($this->service()->promptMessage([
            'catalog_sector_id' => null,
            'catalog_document_type_id' => null,
        ]));
    }

    public function test_el_mensaje_trae_la_ruta_de_dominio_y_el_entregable(): void
    {
        $mensaje = $this->service()->promptMessage($this->coherentPath());

        $this->assertStringContainsString('CLASIFICACIÓN DEL PROYECTO', $mensaje);
        $this->assertStringContainsString(
            'Primario > Recursos naturales > Pesca > Conservación y explotación pesquera y acuicultura',
            $mensaje
        );
        $this->assertStringContainsString('Calidad de productos y servicios > Verificación', $mensaje);
    }

    public function test_por_defecto_prohibe_aportar_hechos_del_dominio(): void
    {
        $mensaje = $this->service()->promptMessage($this->coherentPath());

        // Es encuadre: sirve para terminología y desambiguación...
        $this->assertStringContainsString('terminología', $mensaje);
        // ...y NUNCA como fuente de hechos.
        $this->assertStringContainsString('NO autoriza', $mensaje);
        $this->assertStringContainsString('[Información no disponible]', $mensaje);
        $this->assertStringNotContainsString('PERMISO DE CONOCIMIENTO', $mensaje);
    }

    public function test_con_permiso_de_conocimiento_ancla_el_dominio_en_vez_de_prohibirlo(): void
    {
        $mensaje = $this->service()->promptMessage($this->coherentPath(), usaConocimientoModelo: true);

        // El permiso ya fue otorgado por el tipo de reporte; acá solo se le da
        // un dominio concreto para que el modelo no lo adivine de la entrada.
        $this->assertStringContainsString('PERMISO DE CONOCIMIENTO', $mensaje);
        $this->assertStringContainsString('Primario > Recursos naturales > Pesca', $mensaje);
        // La regla innegociable sigue viva: nada de datos específicos del cliente.
        $this->assertStringContainsString('datos específicos del cliente', $mensaje);
    }

    public function test_incluye_la_configuracion_sugerida_del_documento_elegido(): void
    {
        $mensaje = $this->service()->promptMessage($this->coherentPath());

        // Excel, hoja Estructuras_proyecto, "Calidad de productos y servicios¦Verificación".
        $this->assertStringContainsString('Indicadores sugeridos: 2 a 4', $mensaje);
        $this->assertStringContainsString('¿Requiere tablas?: Sí', $mensaje);
        $this->assertStringContainsString('Clasificación del documento: Documento de verificación', $mensaje);
    }

    public function test_la_configuracion_sugerida_queda_subordinada_al_caso_de_referencia(): void
    {
        $mensaje = $this->service()->promptMessage($this->coherentPath());

        // El maestro manda: "No cambies la estructura, no añadas apartados". La
        // orientación del Excel no puede pelearse con el caso de referencia.
        $this->assertStringContainsString('orientación', $mensaje);
        $this->assertStringContainsString('CASO DE REFERENCIA', $mensaje);
        $this->assertStringContainsString('no agregues apartados', $mensaje);
    }

    public function test_una_clasificacion_parcial_no_rompe_el_mensaje(): void
    {
        $path = $this->coherentPath();

        $mensaje = $this->service()->promptMessage([
            'catalog_sector_id' => $path['catalog_sector_id'],
            'catalog_branch_id' => $path['catalog_branch_id'],
        ]);

        $this->assertStringContainsString('Primario > Recursos naturales', $mensaje);
        // Sin tipo de documento no hay entregable ni configuración sugerida.
        $this->assertStringNotContainsString('Entregable:', $mensaje);
        $this->assertStringNotContainsString('Indicadores sugeridos', $mensaje);
    }

    public function test_solo_entregable_sin_dominio_tambien_produce_mensaje(): void
    {
        $path = $this->coherentPath();

        $mensaje = $this->service()->promptMessage([
            'catalog_service_type_id' => $path['catalog_service_type_id'],
            'catalog_document_type_id' => $path['catalog_document_type_id'],
        ]);

        $this->assertStringContainsString('Calidad de productos y servicios > Verificación', $mensaje);
        $this->assertStringNotContainsString('Dominio:', $mensaje);
    }

    public function test_expectations_devuelve_la_configuracion_del_documento_elegido(): void
    {
        $expectations = $this->service()->expectations($this->coherentPath());

        $this->assertSame('Sí', $expectations['requiere_tablas']);
        $this->assertSame('Sí', $expectations['requiere_formatos']);
        $this->assertSame('No', $expectations['requiere_diagrama']);
    }

    public function test_expectations_vacio_sin_tipo_de_documento(): void
    {
        $this->assertSame([], $this->service()->expectations([]));
    }
}
