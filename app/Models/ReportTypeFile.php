<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReportTypeFile extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'report_type_id',
        'capitulo',
        'tipo_archivo',
        'grupo_id',
        'nombre_original',
        'nombre_archivo',
        'ruta',
        'extension',
        'tamano',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $dates = [
        'deleted_at',
    ];

    public function reportType()
    {
        return $this->belongsTo(ReportType::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deleter()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function getTamanoFormateadoAttribute()
    {
        $bytes = $this->tamano;
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }

    // Query Scopes
    public function scopeByGrupo($query, $grupoId)
    {
        return $query->where('grupo_id', $grupoId);
    }

    public function scopeEntrada($query)
    {
        return $query->where('tipo_archivo', 'entrada');
    }

    public function scopeSalida($query)
    {
        return $query->where('tipo_archivo', 'salida');
    }

    // Get all files in the same group
    public function grupoFiles()
    {
        return $this->hasMany(ReportTypeFile::class, 'grupo_id', 'grupo_id');
    }

    // Get the output file for this group (if this is an input file)
    public function archivoSalida()
    {
        if ($this->tipo_archivo === 'entrada' && $this->grupo_id) {
            return ReportTypeFile::where('grupo_id', $this->grupo_id)
                ->where('tipo_archivo', 'salida')
                ->first();
        }
        return null;
    }

    // Get input files for this group (if this is an output file)
    public function archivosEntrada()
    {
        if ($this->tipo_archivo === 'salida' && $this->grupo_id) {
            return ReportTypeFile::where('grupo_id', $this->grupo_id)
                ->where('tipo_archivo', 'entrada')
                ->get();
        }
        return collect();
    }
}
