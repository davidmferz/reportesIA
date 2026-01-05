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
        $reportTypes = ReportType::withCount('files')->get();
        return view('admin.report-files.index', compact('reportTypes'));
    }

    public function show(ReportType $reportType)
    {
        $reportType->load(['files' => function($query) {
            $query->with('creator')->latest();
        }]);

        return view('admin.report-files.show', compact('reportType'));
    }

    public function create(ReportType $reportType)
    {
        return view('admin.report-files.create', compact('reportType'));
    }

    public function store(Request $request, ReportType $reportType)
    {
        $validated = $request->validate([
            'archivos.*' => 'required|file|max:51200',
        ], [
            'archivos.*.required' => 'Debes seleccionar al menos un archivo.',
            'archivos.*.file' => 'El archivo debe ser válido.',
            'archivos.*.max' => 'El archivo no puede superar los 50MB.',
        ]);

        $archivosSubidos = 0;

        if ($request->hasFile('archivos')) {
            foreach ($request->file('archivos') as $archivo) {
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
                    'nombre_original' => $nombreOriginal,
                    'nombre_archivo' => $nombreArchivo,
                    'ruta' => $ruta,
                    'extension' => $extension,
                    'tamano' => $tamano,
                    'created_by' => Auth::id(),
                ]);

                $archivosSubidos++;
            }
        }

        $mensaje = $archivosSubidos === 1
            ? 'Archivo subido exitosamente.'
            : "{$archivosSubidos} archivos subidos exitosamente.";

        return redirect()->route('admin.report-files.show', $reportType)
            ->with('success', $mensaje);
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
