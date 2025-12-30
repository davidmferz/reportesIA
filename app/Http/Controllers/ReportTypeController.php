<?php

namespace App\Http\Controllers;

use App\Models\ReportType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $reportTypes = ReportType::with('creator', 'updater')->latest()->get();
        return view('admin.report-types.index', compact('reportTypes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Solo administradores pueden crear
        if (!Auth::user()->is_admin) {
            abort(403, 'No tienes permiso para crear tipos de reportes.');
        }

        return view('admin.report-types.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Solo administradores pueden crear
        if (!Auth::user()->is_admin) {
            abort(403, 'No tienes permiso para crear tipos de reportes.');
        }

        $validated = $request->validate([
            'nombre' => 'required|string|max:255|unique:report_types,nombre',
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.unique' => 'Este tipo de reporte ya existe.',
            'nombre.max' => 'El nombre no puede tener más de 255 caracteres.',
        ]);

        $reportType = ReportType::create([
            'nombre' => $validated['nombre'],
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('admin.report-types.index')
            ->with('success', 'Tipo de reporte creado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(ReportType $reportType)
    {
        $reportType->load('creator', 'updater', 'deleter');
        return view('admin.report-types.show', compact('reportType'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ReportType $reportType)
    {
        // Solo administradores pueden editar
        if (!Auth::user()->is_admin) {
            abort(403, 'No tienes permiso para editar tipos de reportes.');
        }

        return view('admin.report-types.edit', compact('reportType'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ReportType $reportType)
    {
        // Solo administradores pueden editar
        if (!Auth::user()->is_admin) {
            abort(403, 'No tienes permiso para editar tipos de reportes.');
        }

        $validated = $request->validate([
            'nombre' => 'required|string|max:255|unique:report_types,nombre,' . $reportType->id,
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.unique' => 'Este tipo de reporte ya existe.',
            'nombre.max' => 'El nombre no puede tener más de 255 caracteres.',
        ]);

        $reportType->update([
            'nombre' => $validated['nombre'],
            'updated_by' => Auth::id(),
        ]);

        return redirect()->route('admin.report-types.index')
            ->with('success', 'Tipo de reporte actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage (soft delete).
     */
    public function destroy(ReportType $reportType)
    {
        // Solo administradores pueden eliminar
        if (!Auth::user()->is_admin) {
            abort(403, 'No tienes permiso para eliminar tipos de reportes.');
        }

        $reportType->update(['deleted_by' => Auth::id()]);
        $reportType->delete();

        return redirect()->route('admin.report-types.index')
            ->with('success', 'Tipo de reporte eliminado exitosamente.');
    }
}
