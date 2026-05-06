<?php

namespace App\Http\Controllers;

use App\Models\ReportType;
use App\Models\ReportTypeFile;
use App\Services\PromptParserService;
use App\Services\AITrainingService;
use App\Services\OutputValidatorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReportTypeFileController extends Controller
{
    public function __construct(
        protected PromptParserService $promptParser,
        protected OutputValidatorService $validator,
    ) {}

    public function index()
    {
        $reportTypes = ReportType::withCount('files')->with('creator')->get();
        return view('admin.report-files.index', compact('reportTypes'));
    }

    /**
     * Muestra el formulario para editar el prompt
     */
    public function editPrompt(ReportType $reportType)
    {
        $reportType->load('creator', 'updater');

        $availableModels = app(AITrainingService::class)->getAvailableModels();
        $promptAnalysis = $this->analyzePromptForView($reportType->prompt);

        return view('admin.report-files.prompt', compact('reportType', 'availableModels', 'promptAnalysis'));
    }

    /**
     * Actualiza el prompt de un tipo de reporte
     */
    public function updatePrompt(Request $request, ReportType $reportType)
    {
        $validated = $request->validate([
            'prompt' => 'nullable|string|max:65535',
            'model' => 'nullable|string|in:gpt-5.5,gpt-5,gpt-5-mini,gpt-5-nano,gpt-4o,gpt-4o-mini,gpt-4-turbo,gpt-3.5-turbo',
            'modo_estricto' => 'nullable|boolean',
        ], [
            'prompt.max' => 'El prompt no puede tener más de 65535 caracteres.',
        ]);

        $reportType->update([
            'prompt' => $validated['prompt'] ?? null,
            'model' => $validated['model'] ?? null,
            'modo_estricto' => (bool) ($validated['modo_estricto'] ?? false),
            'updated_by' => Auth::id(),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Prompt actualizado exitosamente.',
                'analysis' => $this->analyzePromptForView($reportType->prompt),
            ]);
        }

        return redirect()->route('admin.report-files.index')
            ->with('success', 'Prompt actualizado exitosamente.');
    }

    /**
     * Endpoint AJAX: analiza un prompt en vivo (sin guardar) y devuelve
     * palabras prohibidas detectadas, límites de palabras, validaciones.
     */
    public function analyzePrompt(Request $request)
    {
        $validated = $request->validate([
            'prompt' => 'nullable|string|max:65535',
        ]);

        return response()->json($this->analyzePromptForView($validated['prompt'] ?? ''));
    }

    /**
     * Endpoint AJAX: corre el validador determinístico contra un texto de prueba.
     * Permite al usuario probar el prompt sin gastar tokens de OpenAI.
     */
    public function previewValidation(Request $request, ReportType $reportType)
    {
        $validated = $request->validate([
            'sample_output' => 'required|string|max:50000',
            'prompt_override' => 'nullable|string|max:65535',
        ]);

        $prompt = $validated['prompt_override'] ?? $reportType->prompt;
        $result = $this->validator->validate($validated['sample_output'], $prompt);

        return response()->json($result);
    }

    private function analyzePromptForView(?string $prompt): array
    {
        if (empty($prompt)) {
            return [
                'forbidden_terms' => [],
                'word_limits' => ['min' => null, 'max' => null],
                'char_count' => 0,
                'has_structure' => false,
                'warnings' => [],
            ];
        }

        $forbidden = $this->promptParser->extractForbiddenTerms($prompt);
        $limits = $this->promptParser->extractWordLimits($prompt);
        $promptLower = mb_strtolower($prompt, 'UTF-8');

        $warnings = [];
        if (mb_strlen($prompt, 'UTF-8') < 50) {
            $warnings[] = 'El prompt es muy corto. Sé más específico para mejores resultados.';
        }
        if (empty($forbidden)) {
            $warnings[] = 'No se detectaron palabras prohibidas. Usá la sección "PALABRAS PROHIBIDAS:" para listarlas.';
        }
        if ($limits['max'] === null && $limits['min'] === null) {
            $warnings[] = 'Sin límite de palabras detectado. Sin esto, la IA tiende a expandirse.';
        }

        return [
            'forbidden_terms' => $forbidden,
            'word_limits' => $limits,
            'char_count' => mb_strlen($prompt, 'UTF-8'),
            'has_structure' => str_contains($promptLower, 'estructura') || str_contains($promptLower, 'secciones'),
            'warnings' => $warnings,
        ];
    }

    public function show(ReportType $reportType)
    {
        $reportType->load(['files' => function($query) {
            $query->with('creator')
                  ->orderBy('grupo_id')
                  ->orderByRaw("FIELD(tipo_archivo, 'salida', 'entrada')")
                  ->latest();
        }]);

        // Group files by grupo_id for display
        $fileGroups = $reportType->files
            ->whereNotNull('grupo_id')
            ->groupBy('grupo_id');

        // Old files without grupo_id
        $legacyFiles = $reportType->files
            ->whereNull('grupo_id');

        return view('admin.report-files.show', compact('reportType', 'fileGroups', 'legacyFiles'));
    }

    public function create(ReportType $reportType)
    {
        return view('admin.report-files.create', compact('reportType'));
    }

    public function store(Request $request, ReportType $reportType)
    {
        $validated = $request->validate([
            'archivos_entrada' => 'required|array|min:1',
            'archivos_entrada.*' => 'required|file|max:51200',
            'archivo_salida' => 'required|file|max:51200',
        ], [
            'archivos_entrada.required' => 'Debes seleccionar al menos un archivo de entrada.',
            'archivos_entrada.min' => 'Debes seleccionar al menos un archivo de entrada.',
            'archivos_entrada.*.required' => 'Debes seleccionar al menos un archivo de entrada.',
            'archivos_entrada.*.file' => 'El archivo de entrada debe ser válido.',
            'archivos_entrada.*.max' => 'Cada archivo de entrada no puede superar los 50MB.',
            'archivo_salida.required' => 'Debes seleccionar un archivo de salida.',
            'archivo_salida.file' => 'El archivo de salida debe ser válido.',
            'archivo_salida.max' => 'El archivo de salida no puede superar los 50MB.',
        ]);

        $grupoId = (string) Str::uuid();
        $archivosSubidos = 0;

        // Process input files
        if ($request->hasFile('archivos_entrada')) {
            foreach ($request->file('archivos_entrada') as $archivo) {
                $this->storeFile(
                    $archivo,
                    $reportType,
                    $grupoId,
                    null,
                    'entrada'
                );
                $archivosSubidos++;
            }
        }

        // Process output file
        if ($request->hasFile('archivo_salida')) {
            $this->storeFile(
                $request->file('archivo_salida'),
                $reportType,
                $grupoId,
                null,
                'salida'
            );
            $archivosSubidos++;
        }

        $mensaje = "Se han subido {$archivosSubidos} archivos exitosamente.";

        return redirect()->route('admin.report-files.show', $reportType)
            ->with('success', $mensaje);
    }

    private function storeFile($archivo, $reportType, $grupoId, $capitulo, $tipoArchivo)
    {
        $nombreOriginal = $archivo->getClientOriginalName();
        $extension = $archivo->getClientOriginalExtension();
        $nombreArchivo = Str::uuid() . '.' . $extension;
        $tamano = $archivo->getSize();

        $ruta = $archivo->storeAs(
            'report_files/' . $reportType->id,
            $nombreArchivo,
            'local'
        );

        ReportTypeFile::create([
            'report_type_id' => $reportType->id,
            'capitulo' => $capitulo,
            'tipo_archivo' => $tipoArchivo,
            'grupo_id' => $grupoId,
            'nombre_original' => $nombreOriginal,
            'nombre_archivo' => $nombreArchivo,
            'ruta' => $ruta,
            'extension' => $extension,
            'tamano' => $tamano,
            'created_by' => Auth::id(),
        ]);
    }

    public function download(ReportTypeFile $file)
    {
        if (!Storage::disk('local')->exists($file->ruta)) {
            return redirect()->back()->with('error', 'El archivo no existe.');
        }

        return Storage::disk('local')->download($file->ruta, $file->nombre_original);
    }

    public function destroy(ReportTypeFile $file)
    {
        $reportTypeId = $file->report_type_id;

        if (Storage::disk('local')->exists($file->ruta)) {
            Storage::disk('local')->delete($file->ruta);
        }

        $file->update(['deleted_by' => Auth::id()]);
        $file->delete();

        return redirect()->route('admin.report-files.show', $reportTypeId)
            ->with('success', 'Archivo eliminado exitosamente.');
    }
}
