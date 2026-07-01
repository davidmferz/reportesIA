<?php

namespace Tests\Feature;

use App\Models\AIGeneration;
use App\Models\AITraining;
use App\Models\Chapter;
use App\Models\ReportType;
use App\Models\ReportTypeFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminRouteScopingTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    public function test_generation_detail_cannot_be_opened_under_another_report_type(): void
    {
        $admin = $this->admin();
        [$ownerReportType, $generation] = $this->generationForReportType($admin);
        $otherReportType = ReportType::create(['nombre' => 'Otro reporte']);

        $this->actingAs($admin)
            ->get(route('admin.ai-training.generation.show', [$otherReportType, $generation]))
            ->assertNotFound();
    }

    public function test_generation_download_cannot_be_opened_under_another_report_type(): void
    {
        $admin = $this->admin();
        [$ownerReportType, $generation] = $this->generationForReportType($admin);
        $otherReportType = ReportType::create(['nombre' => 'Otro reporte']);

        $this->actingAs($admin)
            ->get(route('admin.ai-training.generation.download', [$otherReportType, $generation]))
            ->assertNotFound();
    }

    public function test_generation_delete_cannot_delete_generation_from_another_report_type(): void
    {
        $admin = $this->admin();
        [$ownerReportType, $generation] = $this->generationForReportType($admin);
        $otherReportType = ReportType::create(['nombre' => 'Otro reporte']);

        $this->actingAs($admin)
            ->delete(route('admin.ai-training.generation.destroy', [$otherReportType, $generation]))
            ->assertNotFound();

        $this->assertDatabaseHas('ai_generations', ['id' => $generation->id]);
    }

    public function test_generation_download_handles_missing_generated_at(): void
    {
        $admin = $this->admin();
        [$reportType, $generation] = $this->generationForReportType($admin);
        $generation->update(['generated_at' => null]);

        $this->actingAs($admin)
            ->get(route('admin.ai-training.generation.download', [$reportType, $generation]))
            ->assertOk()
            ->assertSee('No disponible', false);
    }

    public function test_report_files_show_uses_portable_sql_ordering(): void
    {
        $admin = $this->admin();
        $reportType = ReportType::create(['nombre' => 'Reporte portable']);
        $grupoId = (string) Str::uuid();

        ReportTypeFile::create([
            'report_type_id' => $reportType->id,
            'tipo_archivo' => 'entrada',
            'grupo_id' => $grupoId,
            'nombre_original' => 'entrada.txt',
            'nombre_archivo' => 'entrada.txt',
            'ruta' => 'report_files/test/entrada.txt',
            'extension' => 'txt',
            'tamano' => 10,
            'created_by' => $admin->id,
        ]);

        ReportTypeFile::create([
            'report_type_id' => $reportType->id,
            'tipo_archivo' => 'salida',
            'grupo_id' => $grupoId,
            'nombre_original' => 'salida.txt',
            'nombre_archivo' => 'salida.txt',
            'ruta' => 'report_files/test/salida.txt',
            'extension' => 'txt',
            'tamano' => 10,
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.report-files.show', $reportType))
            ->assertOk();
    }

    /** @return array{0: ReportType, 1: AIGeneration} */
    private function generationForReportType(User $admin): array
    {
        $reportType = ReportType::create(['nombre' => 'Reporte dueño']);
        $chapter = Chapter::create([
            'report_type_id' => $reportType->id,
            'nombre' => 'Capítulo',
            'orden' => 1,
        ]);
        $training = AITraining::create([
            'report_type_id' => $reportType->id,
            'status' => 'ready',
            'system_prompt' => 'Prompt',
            'examples_count' => 1,
        ]);
        $generation = AIGeneration::create([
            'ai_training_id' => $training->id,
            'user_id' => $admin->id,
            'chapter_id' => $chapter->id,
            'titulo' => 'Generación',
            'input_content' => 'input',
            'output_content' => 'output',
            'status' => 'completed',
            'generated_at' => now(),
        ]);

        return [$reportType, $generation];
    }
}
