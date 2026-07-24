<?php

namespace App\Http\Controllers;

use App\Models\Chapter;
use App\Models\ReportType;
use App\Services\ChapterStructureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChapterController extends Controller
{
    public function index(ReportType $reportType)
    {
        $chapters = $reportType->chapters()->with('creator')->get();

        // Estructura sugerida por el catálogo, si el tipo de reporte está clasificado.
        $reportType->loadMissing('catalogServiceType', 'catalogDocumentType.sections');
        $estructuraSugerida = $reportType->catalogDocumentType?->sections ?? collect();

        return view('admin.chapters.index', compact('reportType', 'chapters', 'estructuraSugerida'));
    }

    /**
     * Crea los capítulos del tipo de reporte a partir de la estructura del catálogo.
     */
    public function applyStructure(Request $request, ReportType $reportType, ChapterStructureService $structures)
    {
        if (!Auth::user()->is_admin) {
            abort(403, 'No tienes permiso para crear capítulos.');
        }

        try {
            $creados = $structures->applyTo($reportType, $request->boolean('replace'));
        } catch (\RuntimeException $e) {
            return redirect()->route('admin.chapters.index', $reportType)
                ->with('error', $e->getMessage());
        }

        return redirect()->route('admin.chapters.index', $reportType)
            ->with('success', "Se cargaron {$creados} capítulos desde la estructura sugerida del catálogo.");
    }

    public function create(ReportType $reportType)
    {
        if (!Auth::user()->is_admin) {
            abort(403, 'No tienes permiso para crear capítulos.');
        }

        return view('admin.chapters.create', compact('reportType'));
    }

    public function store(Request $request, ReportType $reportType)
    {
        if (!Auth::user()->is_admin) {
            abort(403, 'No tienes permiso para crear capítulos.');
        }

        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:1000',
            'orden' => 'nullable|integer|min:0',
        ], [
            'nombre.required' => 'El nombre del capítulo es obligatorio.',
            'nombre.max' => 'El nombre no puede tener más de 255 caracteres.',
            'descripcion.max' => 'La descripción no puede tener más de 1000 caracteres.',
        ]);

        $maxOrden = $reportType->chapters()->max('orden') ?? -1;

        Chapter::create([
            'report_type_id' => $reportType->id,
            'nombre' => $validated['nombre'],
            'descripcion' => $validated['descripcion'] ?? null,
            'orden' => $validated['orden'] ?? ($maxOrden + 1),
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('admin.chapters.index', $reportType)
            ->with('success', 'Capítulo creado exitosamente.');
    }

    public function edit(ReportType $reportType, Chapter $chapter)
    {
        if (!Auth::user()->is_admin) {
            abort(403, 'No tienes permiso para editar capítulos.');
        }

        if ($chapter->report_type_id !== $reportType->id) {
            abort(404);
        }

        return view('admin.chapters.edit', compact('reportType', 'chapter'));
    }

    public function update(Request $request, ReportType $reportType, Chapter $chapter)
    {
        if (!Auth::user()->is_admin) {
            abort(403, 'No tienes permiso para editar capítulos.');
        }

        if ($chapter->report_type_id !== $reportType->id) {
            abort(404);
        }

        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:1000',
            'orden' => 'nullable|integer|min:0',
        ], [
            'nombre.required' => 'El nombre del capítulo es obligatorio.',
            'nombre.max' => 'El nombre no puede tener más de 255 caracteres.',
            'descripcion.max' => 'La descripción no puede tener más de 1000 caracteres.',
        ]);

        $chapter->update([
            'nombre' => $validated['nombre'],
            'descripcion' => $validated['descripcion'] ?? null,
            'orden' => $validated['orden'] ?? $chapter->orden,
            'updated_by' => Auth::id(),
        ]);

        return redirect()->route('admin.chapters.index', $reportType)
            ->with('success', 'Capítulo actualizado exitosamente.');
    }

    public function destroy(ReportType $reportType, Chapter $chapter)
    {
        if (!Auth::user()->is_admin) {
            abort(403, 'No tienes permiso para eliminar capítulos.');
        }

        if ($chapter->report_type_id !== $reportType->id) {
            abort(404);
        }

        $chapter->update(['deleted_by' => Auth::id()]);
        $chapter->delete();

        return redirect()->route('admin.chapters.index', $reportType)
            ->with('success', 'Capítulo eliminado exitosamente.');
    }
}
