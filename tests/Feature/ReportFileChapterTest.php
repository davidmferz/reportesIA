<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\ReportType;
use App\Models\ReportTypeFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * La pantalla de subir ejemplos no preguntaba a qué capítulo pertenecen: el
 * controlador pasaba `null` fijo a storeFile(). La columna existía desde enero
 * y nunca se llenó, así que todos los ejemplos quedaban sin capítulo y el
 * few-shot no podía distinguir a cuál corresponde cada caso de referencia.
 */
class ReportFileChapterTest extends TestCase
{
    use RefreshDatabase;

    private ReportType $reportType;
    private Chapter $capituloUno;
    private Chapter $capituloDos;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->reportType = ReportType::create(['nombre' => 'Acciones de Posicionamiento']);
        $this->capituloUno = Chapter::create([
            'report_type_id' => $this->reportType->id,
            'nombre' => 'Análisis de Mercado',
            'orden' => 1,
        ]);
        $this->capituloDos = Chapter::create([
            'report_type_id' => $this->reportType->id,
            'nombre' => 'Conclusiones',
            'orden' => 2,
        ]);
    }

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function payload(array $extra = []): array
    {
        return [
            'archivos_entrada' => [UploadedFile::fake()->create('entrada.txt', 10)],
            'archivo_salida' => UploadedFile::fake()->create('salida.txt', 10),
        ] + $extra;
    }

    public function test_la_pantalla_ofrece_los_capitulos_del_tipo_de_reporte(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.report-files.create', $this->reportType))
            ->assertOk()
            ->assertSee('chapter_id', false)
            ->assertSee('Análisis de Mercado', false)
            ->assertSee('Conclusiones', false);
    }

    public function test_la_pantalla_no_ofrece_capitulos_de_otro_tipo_de_reporte(): void
    {
        $otro = ReportType::create(['nombre' => 'Otro reporte']);
        Chapter::create([
            'report_type_id' => $otro->id,
            'nombre' => 'Capítulo Ajeno',
            'orden' => 1,
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.report-files.create', $this->reportType))
            ->assertOk()
            ->assertDontSee('Capítulo Ajeno', false);
    }

    public function test_el_capitulo_se_guarda_en_todos_los_archivos_del_grupo(): void
    {
        $this->actingAs($this->admin())
            ->post(
                route('admin.report-files.store', $this->reportType),
                $this->payload(['chapter_id' => $this->capituloUno->id])
            )
            ->assertRedirect();

        $archivos = ReportTypeFile::all();

        $this->assertCount(2, $archivos, 'Deberían guardarse la entrada y la salida.');

        foreach ($archivos as $archivo) {
            $this->assertSame(
                $this->capituloUno->id,
                $archivo->chapter_id,
                "El archivo de {$archivo->tipo_archivo} quedó sin capítulo."
            );
        }
    }

    public function test_el_capitulo_sigue_siendo_opcional(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.report-files.store', $this->reportType), $this->payload())
            ->assertRedirect();

        $this->assertCount(2, ReportTypeFile::all());
        $this->assertNull(ReportTypeFile::first()->chapter_id);
    }

    public function test_rechaza_un_capitulo_de_otro_tipo_de_reporte(): void
    {
        $otro = ReportType::create(['nombre' => 'Otro reporte']);
        $ajeno = Chapter::create([
            'report_type_id' => $otro->id,
            'nombre' => 'Capítulo Ajeno',
            'orden' => 1,
        ]);

        $this->actingAs($this->admin())
            ->post(
                route('admin.report-files.store', $this->reportType),
                $this->payload(['chapter_id' => $ajeno->id])
            )
            ->assertSessionHasErrors('chapter_id');

        $this->assertSame(0, ReportTypeFile::count());
    }

    public function test_la_pantalla_no_se_rompe_si_el_tipo_no_tiene_capitulos(): void
    {
        $pelado = ReportType::create(['nombre' => 'Sin capítulos']);

        $this->actingAs($this->admin())
            ->get(route('admin.report-files.create', $pelado))
            ->assertOk();
    }

    /**
     * Sin esto el capítulo se guarda pero el usuario no lo ve en ningún lado:
     * la pantalla listaba `capitulo`, la columna vieja que nadie llenaba nunca.
     */
    public function test_el_listado_muestra_el_capitulo_del_grupo(): void
    {
        $this->actingAs($this->admin())
            ->post(
                route('admin.report-files.store', $this->reportType),
                $this->payload(['chapter_id' => $this->capituloUno->id])
            );

        $this->actingAs($this->admin())
            ->get(route('admin.report-files.show', $this->reportType))
            ->assertOk()
            ->assertSee('Análisis de Mercado', false);
    }

    public function test_el_listado_no_se_rompe_con_un_grupo_sin_capitulo(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.report-files.store', $this->reportType), $this->payload());

        $this->actingAs($this->admin())
            ->get(route('admin.report-files.show', $this->reportType))
            ->assertOk()
            ->assertSee('Grupo de Archivos', false);
    }

    public function test_el_archivo_expone_el_capitulo_como_relacion(): void
    {
        $this->actingAs($this->admin())
            ->post(
                route('admin.report-files.store', $this->reportType),
                $this->payload(['chapter_id' => $this->capituloDos->id])
            );

        $this->assertSame('Conclusiones', ReportTypeFile::first()->chapter->nombre);
    }
}
