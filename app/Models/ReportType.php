<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReportType extends Model
{
    use SoftDeletes;
    use LogsActivity;

    protected $fillable = [
        'nombre',
        'prompt',
        'model',
        'modo_estricto',
        'usa_internet',
        'usa_conocimiento_modelo',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'modo_estricto' => 'boolean',
        'usa_internet' => 'boolean',
        'usa_conocimiento_modelo' => 'boolean',
    ];

    protected $dates = [
        'deleted_at',
    ];

    // Relaciones de auditoría
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

    public function files()
    {
        return $this->hasMany(ReportTypeFile::class);
    }

    public function chapters()
    {
        return $this->hasMany(Chapter::class)->orderBy('orden');
    }

    /**
     * Relación con el entrenamiento de IA
     */
    public function training()
    {
        return $this->hasOne(AITraining::class);
    }

    /**
     * Verifica si el tipo de reporte tiene un entrenamiento listo
     */
    public function hasReadyTraining(): bool
    {
        return $this->training && $this->training->status === 'ready';
    }
}
