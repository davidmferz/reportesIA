<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    /**
     * Lista el registro de actividad (audit log) con filtros por usuario, modelo,
     * tipo de evento y rango de fechas. Sirve para rastrear qué hace el cliente
     * en sus pruebas y poder replicarlo.
     */
    public function index(Request $request)
    {
        $query = ActivityLog::with('causer')->latest();

        if ($request->filled('causer_id')) {
            $query->where('causer_id', $request->input('causer_id'));
        }

        if ($request->filled('log_name')) {
            $query->where('log_name', $request->input('log_name'));
        }

        if ($request->filled('event')) {
            $query->where('event', $request->input('event'));
        }

        if ($request->filled('desde')) {
            $query->whereDate('created_at', '>=', $request->input('desde'));
        }

        if ($request->filled('hasta')) {
            $query->whereDate('created_at', '<=', $request->input('hasta'));
        }

        $logs = $query->paginate(25)->withQueryString();

        $users = User::orderBy('name')->get(['id', 'name', 'email']);
        $logNames = ActivityLog::query()
            ->select('log_name')
            ->distinct()
            ->orderBy('log_name')
            ->pluck('log_name')
            ->filter()
            ->values();

        return view('admin.activity-logs.index', compact('logs', 'users', 'logNames'));
    }
}
