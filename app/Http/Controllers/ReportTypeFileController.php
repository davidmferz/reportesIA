<?php

namespace App\Http\Controllers;

use App\Models\ReportType;
use App\Models\ReportTypeFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReportTypeFileController extends Controller
{
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
        return view('admin.report-files.prompt', compact('reportType'));
    }

    /**
     * Actualiza el prompt de un tipo de reporte
     */
    public function updatePrompt(Request $request, ReportType $reportType)
    {
        $validated = $request->validate([
            'prompt' => 'nullable|string|max:65535',
        ], [
            'prompt.max' => 'El prompt no puede tener más de 65535 caracteres.',
        ]);

        $reportType->update([
            'prompt' => $validated['prompt'],
            'updated_by' => Auth::id(),
        ]);

        // Si es una petición AJAX, devolver JSON
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Prompt actualizado exitosamente.',
            ]);
        }

        // Si es una petición normal de formulario, redirigir
        return redirect()->route('admin.report-files.index')
            ->with('success', 'Prompt actualizado exitosamente.');
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
