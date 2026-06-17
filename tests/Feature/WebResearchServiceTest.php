<?php

namespace Tests\Feature;

use App\Services\WebResearchService;
use OpenAI\Laravel\Facades\OpenAI;
use OpenAI\Responses\Responses\CreateResponse;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Blinda el servicio que conecta la generación a internet (web_search nativo de
 * OpenAI). La garantía clave: el servicio es FAIL-SAFE — ante cualquier error
 * devuelve used=false para que la generación NUNCA se rompa por prender internet.
 */
class WebResearchServiceTest extends TestCase
{
    public function test_devuelve_brief_cuando_la_busqueda_responde(): void
    {
        OpenAI::fake([
            CreateResponse::fake(),
        ]);

        $result = (new WebResearchService())->research('sistema de riego en berries');

        $this->assertTrue($result['used']);
        $this->assertNotSame('', trim($result['brief']));
    }

    public function test_es_fail_safe_ante_error_de_la_api(): void
    {
        // Si la API tira (sin saldo, modelo sin web_search, timeout), NO debe propagar.
        OpenAI::fake([
            new \RuntimeException('boom'),
        ]);

        $result = (new WebResearchService())->research('cualquier tema');

        $this->assertFalse($result['used']);
        $this->assertSame('', $result['brief']);
        $this->assertSame([], $result['sources']);
    }

    public function test_no_consulta_la_api_si_el_tema_esta_vacio(): void
    {
        OpenAI::fake([]); // si intentara llamar, el fake sin respuestas lanzaría

        $result = (new WebResearchService())->research('   ');

        $this->assertFalse($result['used']);
    }

    public function test_extrae_urls_unicas_del_brief(): void
    {
        $service = new WebResearchService();
        $method = new ReflectionMethod($service, 'extractSources');

        $brief = "Resumen técnico.\n\nFUENTES:\n"
            . "https://ejemplo.com/normativa-riego\n"
            . "https://otra.org/buenas-practicas\n"
            . "https://ejemplo.com/normativa-riego"; // duplicada a propósito

        $sources = $method->invoke($service, $brief);

        $this->assertEqualsCanonicalizing([
            'https://ejemplo.com/normativa-riego',
            'https://otra.org/buenas-practicas',
        ], $sources);
    }
}
