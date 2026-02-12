<?php

namespace App\Http\Controllers;

use App\Models\AIGeneration;
use App\Models\AITraining;
use App\Models\ReportType;
use App\Services\AITrainingService;
use App\Services\DocumentExtractorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AITrainingController extends Controller
{
    protected AITrainingService $trainingService;
    protected DocumentExtractorService $extractorService;

    public function __construct(AITrainingService $trainingService, DocumentExtractorService $extractorService)
    {
        $this->trainingService = $trainingService;
        $this->extractorService = $extractorService;
    }

    /**
     * Muestra la lista de tipos de reporte con su estado de entrenamiento
     */
    public function index()
    {
        $reportTypes = ReportType::withCount('files')
            ->with(['training' => function ($query) {
                $query->withCount('examples');
            }])
            ->get();

        return view('admin.ai-training.index', compact('reportTypes'));
    }

    /**
     * Muestra el detalle del entrenamiento para un tipo de reporte
     */
    public function show(ReportType $reportType)
    {
        $reportType->load(['files' => function ($query) {
            $query->whereNotNull('grupo_id')
                  ->orderBy('grupo_id')
                  ->orderBy('tipo_archivo');
        }]);

        $training = AITraining::with('examples')
            ->where('report_type_id', $reportType->id)
            ->first();

        // Agrupar archivos por grupo_id
        $fileGroups = $reportType->files
            ->whereNotNull('grupo_id')
            ->groupBy('grupo_id');

        return view('admin.ai-training.show', compact('reportType', 'training', 'fileGroups'));
    }

    /**
     * Entrena la IA con los ejemplos del tipo de reporte
     */
    public function train(ReportType $reportType)
    {
        try {
            // Verificar que haya archivos para entrenar
            $filesCount = $reportType->files()
                ->whereNotNull('grupo_id')
                ->count();

            if ($filesCount === 0) {
                return redirect()->back()
                    ->with('error', 'No hay archivos de entrenamiento disponibles. Primero sube archivos de entrada y salida.');
            }

            // Procesar el entrenamiento
            $training = $this->trainingService->processTraining($reportType);

            if ($training->status === 'ready') {
                return redirect()->route('admin.ai-training.show', $reportType)
                    ->with('success', "Entrenamiento completado exitosamente. Se procesaron {$training->examples_count} ejemplos.");
            } else {
                return redirect()->route('admin.ai-training.show', $reportType)
                    ->with('error', "Error en el entrenamiento: {$training->error_message}");
            }

        } catch (\Exception $e) {
            Log::error("Error en entrenamiento: " . $e->getMessage());

            return redirect()->back()
                ->with('error', "Error al procesar el entrenamiento: {$e->getMessage()}");
        }
    }

    /**
     * Muestra el formulario para generar una nueva salida
     */
    public function createGeneration(ReportType $reportType)
    {
        $training = AITraining::where('report_type_id', $reportType->id)
            ->where('status', 'ready')
            ->first();

        if (!$training) {
            return redirect()->route('admin.ai-training.show', $reportType)
                ->with('error', 'El entrenamiento no está listo. Primero debes entrenar la IA.');
        }

        // Verificar estado de OpenAI
        $openaiStatus = $this->trainingService->checkOpenAIStatus();

        // Cargar los capítulos del tipo de reporte ordenados
        $chapters = $reportType->chapters()->orderBy('orden')->get();

        return view('admin.ai-training.generate', compact('reportType', 'training', 'chapters', 'openaiStatus'));
    }

    /**
     * Genera una salida usando la IA entrenada
     */
    public function generate(Request $request, ReportType $reportType)
    {
        $request->validate([
            'chapter_id' => 'required|exists:chapters,id',
            'archivos' => 'required|array|min:1',
            'archivos.*' => 'required|file|max:51200',
        ], [
            'chapter_id.required' => 'Debes seleccionar un capítulo.',
            'chapter_id.exists' => 'El capítulo seleccionado no existe.',
            'archivos.required' => 'Debes subir al menos un archivo de entrada.',
            'archivos.min' => 'Debes subir al menos un archivo de entrada.',
            'archivos.*.max' => 'Cada archivo no puede superar los 50MB.',
        ]);

        $training = AITraining::where('report_type_id', $reportType->id)
            ->where('status', 'ready')
            ->first();

        if (!$training) {
            return redirect()->back()
                ->with('error', 'El entrenamiento no está listo.');
        }

        // Obtener el capítulo seleccionado
        $chapter = \App\Models\Chapter::findOrFail($request->chapter_id);

        // Verificar que el capítulo pertenece al tipo de reporte
        if ($chapter->report_type_id !== $reportType->id) {
            return redirect()->back()
                ->with('error', 'El capítulo no pertenece a este tipo de reporte.');
        }

        try {
            // Crear registro de generación con el capítulo
            $generation = AIGeneration::create([
                'ai_training_id' => $training->id,
                'user_id' => Auth::id(),
                'chapter_id' => $chapter->id,
                'titulo' => $chapter->nombre . ' - ' . now()->format('d/m/Y H:i'),
                'input_content' => '',
                'status' => 'processing',
            ]);

            // Procesar archivos de entrada
            $inputFiles = [];
            $inputContent = '';

            foreach ($request->file('archivos') as $archivo) {
                $nombreArchivo = Str::uuid() . '.' . $archivo->getClientOriginalExtension();
                $ruta = $archivo->storeAs(
                    'ai_generations/' . $generation->id,
                    $nombreArchivo,
                    'local'
                );

                $inputFiles[] = [
                    'name' => $archivo->getClientOriginalName(),
                    'path' => $ruta,
                ];

                $inputContent .= "--- Archivo: {$archivo->getClientOriginalName()} ---\n";
                $inputContent .= $this->extractorService->extractText($ruta);
                $inputContent .= "\n\n";
            }

            $generation->update(['input_content' => $inputContent]);

            // Generar salida con IA
            $result = $this->trainingService->generateOutput($training, $inputFiles);

            if ($result['success']) {
                $generation->update([
                    'output_content' => $result['content'],
                    'status' => 'completed',
                    'prompt_tokens' => $result['usage']['prompt_tokens'] ?? null,
                    'completion_tokens' => $result['usage']['completion_tokens'] ?? null,
                    'total_tokens' => $result['usage']['total_tokens'] ?? null,
                    'generated_at' => now(),
                ]);

                return redirect()->route('admin.ai-training.generation.show', [$reportType, $generation])
                    ->with('success', 'Documento generado exitosamente.');
            } else {
                $generation->update([
                    'status' => 'error',
                    'error_message' => $result['error'],
                ]);

                return redirect()->back()
                    ->with('error', "Error al generar: {$result['error']}");
            }

        } catch (\Exception $e) {
            Log::error("Error generando documento: " . $e->getMessage());

            if (isset($generation)) {
                $generation->update([
                    'status' => 'error',
                    'error_message' => $e->getMessage(),
                ]);
            }

            return redirect()->back()
                ->with('error', "Error: {$e->getMessage()}");
        }
    }

    /**
     * Muestra el resultado de una generación
     */
    public function showGeneration(ReportType $reportType, AIGeneration $generation)
    {
        $generation->load(['user', 'chapter']);
        return view('admin.ai-training.generation-show', compact('reportType', 'generation'));
    }

    /**
     * Lista todas las generaciones de un tipo de reporte
     */
    public function generations(ReportType $reportType)
    {
        $training = AITraining::where('report_type_id', $reportType->id)->first();

        $generations = $training
            ? $training->generations()->with(['user', 'chapter'])->latest()->paginate(20)
            : collect();

        return view('admin.ai-training.generations', compact('reportType', 'training', 'generations'));
    }

    /**
     * Elimina una generación
     */
    public function destroyGeneration(ReportType $reportType, AIGeneration $generation)
    {
        // Eliminar archivos temporales
        Storage::disk('local')->deleteDirectory('ai_generations/' . $generation->id);

        $generation->delete();

        return redirect()->route('admin.ai-training.generations', $reportType)
            ->with('success', 'Generación eliminada exitosamente.');
    }

    /**
     * Descarga el contenido generado como archivo Markdown
     */
    public function downloadGeneration(ReportType $reportType, AIGeneration $generation)
    {
        if (!$generation->output_content) {
            return redirect()->back()
                ->with('error', 'No hay contenido para descargar.');
        }

        $filename = Str::slug($generation->titulo ?? 'documento-generado') . '.md';
        $content = "# {$generation->titulo}\n\n";
        $content .= "**Generado el:** " . $generation->generated_at->format('d/m/Y H:i') . "\n";
        $content .= "**Tipo de Reporte:** {$reportType->nombre}\n\n";
        $content .= "---\n\n";
        $content .= $generation->output_content;

        return response($content)
            ->header('Content-Type', 'text/markdown')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }
}
