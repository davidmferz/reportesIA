<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AITraining extends Model
{
    protected $table = 'ai_trainings';

    protected $fillable = [
        'report_type_id',
        'status',
        'system_prompt',
        'examples_count',
        'error_message',
        'last_trained_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'last_trained_at' => 'datetime',
        'examples_count' => 'integer',
    ];

    /**
     * Relación con el tipo de reporte
     */
    public function reportType(): BelongsTo
    {
        return $this->belongsTo(ReportType::class);
    }

    /**
     * Relación con los ejemplos de entrenamiento
     */
    public function examples(): HasMany
    {
        return $this->hasMany(AITrainingExample::class, 'ai_training_id');
    }

    /**
     * Relación con las generaciones
     */
    public function generations(): HasMany
    {
        return $this->hasMany(AIGeneration::class, 'ai_training_id');
    }

    /**
     * Usuario que creó el entrenamiento
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Usuario que actualizó el entrenamiento
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Verifica si el entrenamiento está listo para usar
     */
    public function isReady(): bool
    {
        return $this->status === 'ready';
    }

    /**
     * Verifica si el entrenamiento tiene errores
     */
    public function hasError(): bool
    {
        return $this->status === 'error';
    }

    /**
     * Obtiene el badge de estado para la UI
     */
    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'pending' => ['color' => 'gray', 'text' => 'Pendiente'],
            'processing' => ['color' => 'yellow', 'text' => 'Procesando'],
            'ready' => ['color' => 'green', 'text' => 'Listo'],
            'error' => ['color' => 'red', 'text' => 'Error'],
            default => ['color' => 'gray', 'text' => 'Desconocido'],
        };
    }
}
