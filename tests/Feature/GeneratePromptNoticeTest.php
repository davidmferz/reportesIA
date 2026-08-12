<?php

namespace Tests\Feature;

use App\Models\AITraining;
use App\Models\ReportType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El cliente escribió un prompt correctivo para su segunda prueba, creyó haberlo
 * aplicado, y el sistema nunca lo recibió: el campo vive tres niveles adentro, en
 * "Gestión de Archivos", y un tipo de reporte recién creado arranca con él vacío
 * sin que nada lo señale. La pantalla de generar mostraba exactamente lo mismo con
 * instrucciones cargadas que sin ellas.
 *
 * Este aviso ataca el instante en que falló: el momento antes de generar. Es
 * aditivo a propósito —no mueve el campo— porque mover rompe la costumbre de los
 * tipos que ya lo tienen cargado y no garantiza que el próximo lo encuentre.
 *
 * Además, estos tests RENDERIZAN el blade de verdad: los unit tests de prompts son
 * PHPUnit puro y no ejecutan una línea de Blade.
 */
class GeneratePromptNoticeTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function reportTypeEntrenado(?string $prompt): ReportType
    {
        $reportType = ReportType::create([
            'nombre' => 'Metodología de gestión comercial',
            'prompt' => $prompt,
        ]);

        AITraining::create([
            'report_type_id' => $reportType->id,
            'status' => 'ready',
            'system_prompt' => 'Prompt maestro persistido.',
            'examples_count' => 2,
        ]);

        return $reportType;
    }

    public function test_avisa_cuando_el_tipo_de_reporte_no_tiene_instrucciones(): void
    {
        $reportType = $this->reportTypeEntrenado(null);

        $this->actingAs($this->admin())
            ->get(route('admin.ai-training.generate.create', $reportType))
            ->assertOk()
            ->assertSee('no tiene instrucciones cargadas', false)
            ->assertSee(route('admin.report-files.prompt', $reportType), false);
    }

    public function test_confirma_cuando_las_instrucciones_estan_cargadas(): void
    {
        $reportType = $this->reportTypeEntrenado(str_repeat('a', 2140));

        $response = $this->actingAs($this->admin())
            ->get(route('admin.ai-training.generate.create', $reportType))
            ->assertOk()
            ->assertSee('Instrucciones del proyecto cargadas', false);

        // El aviso de ausencia NO puede aparecer cuando sí hay instrucciones.
        $response->assertDontSee('no tiene instrucciones cargadas', false);
    }

    /**
     * Un prompt de solo espacios es indistinguible de uno vacío para la IA. Si el
     * aviso se guiara por `!== null` en vez de por contenido real, diría que todo
     * está bien mientras el modelo no recibe nada.
     */
    public function test_un_prompt_en_blanco_cuenta_como_ausente(): void
    {
        $reportType = $this->reportTypeEntrenado("   \n\t  ");

        $this->actingAs($this->admin())
            ->get(route('admin.ai-training.generate.create', $reportType))
            ->assertOk()
            ->assertSee('no tiene instrucciones cargadas', false);
    }

    /**
     * El conteo le da al usuario una forma de verificar de un vistazo que lo que
     * pegó entró completo, sin abrir la otra pantalla.
     */
    public function test_muestra_el_tamano_de_las_instrucciones(): void
    {
        $reportType = $this->reportTypeEntrenado(str_repeat('a', 2140));

        $this->actingAs($this->admin())
            ->get(route('admin.ai-training.generate.create', $reportType))
            ->assertSee('2,140', false);
    }
}
