<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\ReportType;
use App\Models\ReportTypeFile;
use App\Services\AITrainingService;
use App\Services\DocumentExtractorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mockery\MockInterface;
use ReflectionMethod;
use Tests\TestCase;

/**
 * El capítulo del archivo tiene que sobrevivir al entrenamiento y llegar al
 * few-shot. Antes no llegaba nada: `capitulo` se guardaba como el string
 * 'Sin capítulo' por el fallback de processTraining, y como ese string es
 * truthy, el prompt terminaba diciendo literal "CASO DE REFERENCIA 1 (Sin
 * capítulo)". Ruido puro dentro del Prompt Maestro.
 */
class TrainingExampleChapterTest extends TestCase
{
    use RefreshDatabase;

    private ReportType $reportType;
    private Chapter $capitulo;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->reportType = ReportType::create(['nombre' => 'Acciones de Posicionamiento']);
        $this->capitulo = Chapter::create([
            'report_type_id' => $this->reportType->id,
            'nombre' => 'Análisis de Mercado',
            'orden' => 1,
        ]);

        $this->mock(DocumentExtractorService::class, function (MockInterface $mock) {
            $mock->shouldReceive('extractText')->andReturn(str_repeat('contenido de prueba. ', 30));
        });
    }

    /**
     * Crea el par entrada+salida que processTraining espera para armar un ejemplo.
     */
    private function grupoDeArchivos(?int $chapterId): string
    {
        $grupoId = (string) Str::uuid();

        foreach (['entrada', 'salida'] as $tipo) {
            $ruta = "report_files/{$this->reportType->id}/{$tipo}-{$grupoId}.txt";
            Storage::disk('local')->put($ruta, 'contenido');

            ReportTypeFile::create([
                'report_type_id' => $this->reportType->id,
                'chapter_id' => $chapterId,
                'tipo_archivo' => $tipo,
                'grupo_id' => $grupoId,
                'nombre_original' => "{$tipo}.txt",
                'nombre_archivo' => "{$tipo}-{$grupoId}.txt",
                'ruta' => $ruta,
                'extension' => 'txt',
                'tamano' => 100,
            ]);
        }

        return $grupoId;
    }

    private function service(): AITrainingService
    {
        return app(AITrainingService::class);
    }

    public function test_el_entrenamiento_copia_el_capitulo_al_ejemplo(): void
    {
        $this->grupoDeArchivos($this->capitulo->id);

        $training = $this->service()->processTraining($this->reportType);
        $ejemplo = $training->examples()->firstOrFail();

        $this->assertSame($this->capitulo->id, $ejemplo->chapter_id);
        $this->assertSame('Análisis de Mercado', $ejemplo->capitulo);
    }

    public function test_sin_capitulo_el_ejemplo_queda_nulo_y_no_con_un_centinela(): void
    {
        $this->grupoDeArchivos(null);

        $training = $this->service()->processTraining($this->reportType);
        $ejemplo = $training->examples()->firstOrFail();

        $this->assertNull($ejemplo->chapter_id);
        // 'Sin capítulo' era truthy y se colaba al prompt. Ahora es null de verdad.
        $this->assertNull($ejemplo->capitulo);
    }

    public function test_el_few_shot_etiqueta_el_caso_de_referencia_con_su_capitulo(): void
    {
        $metodo = new ReflectionMethod($this->service(), 'buildReferenceExampleMessages');

        $mensajes = $metodo->invoke($this->service(), [
            ['capitulo' => 'Análisis de Mercado', 'input' => 'entrada', 'output' => 'salida'],
        ]);

        $this->assertStringContainsString('CASO DE REFERENCIA 1 (Análisis de Mercado)', $mensajes[0]['content']);
    }

    public function test_el_few_shot_omite_la_etiqueta_cuando_no_hay_capitulo(): void
    {
        $metodo = new ReflectionMethod($this->service(), 'buildReferenceExampleMessages');

        $mensajes = $metodo->invoke($this->service(), [
            ['capitulo' => null, 'input' => 'entrada', 'output' => 'salida'],
        ]);

        $this->assertStringContainsString('CASO DE REFERENCIA 1', $mensajes[0]['content']);
        $this->assertStringNotContainsString('Sin capítulo', $mensajes[0]['content']);
        $this->assertStringNotContainsString('(', $mensajes[0]['content']);
    }
}
