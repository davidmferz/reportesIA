<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AIGeneration extends Model
{
    protected $table = 'ai_generations';

    protected $fillable = [
        'ai_training_id',
        'user_id',
        'chapter_id',
        'titulo',
        'input_content',
        'output_content',
        'status',
        'error_message',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'generated_at',
        'validation_passed',
        'validation_result',
        'validation_attempts',
        'sanitized_post_hoc',
        'truncated_post_hoc',
        'prompt_messages',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
        'prompt_tokens' => 'integer',
        'completion_tokens' => 'integer',
        'total_tokens' => 'integer',
        'validation_passed' => 'boolean',
        'validation_result' => 'array',
        'validation_attempts' => 'integer',
        'sanitized_post_hoc' => 'boolean',
        'truncated_post_hoc' => 'boolean',
        'prompt_messages' => 'array',
    ];

    /**
     * Relación con el entrenamiento
     */
    public function training(): BelongsTo
    {
        return $this->belongsTo(AITraining::class, 'ai_training_id');
    }

    /**
     * Relación con el usuario
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación con el capítulo
     */
    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    /**
     * Obtiene el badge de estado para la UI
     */
    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'pending' => ['color' => 'gray', 'text' => 'Pendiente'],
            'processing' => ['color' => 'yellow', 'text' => 'Procesando'],
            'completed' => ['color' => 'green', 'text' => 'Completado'],
            'error' => ['color' => 'red', 'text' => 'Error'],
            default => ['color' => 'gray', 'text' => 'Desconocido'],
        };
    }

    /**
     * Verifica si la generación fue exitosa
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Verifica si hubo un error
     */
    public function hasError(): bool
    {
        return $this->status === 'error';
    }

    /**
     * Obtiene una versión truncada del contenido de entrada
     */
    public function getTruncatedInputAttribute(): string
    {
        return strlen($this->input_content) > 200
            ? substr($this->input_content, 0, 200) . '...'
            : $this->input_content;
    }

    /**
     * Obtiene una versión truncada del contenido de salida
     */
    public function getTruncatedOutputAttribute(): string
    {
        if (!$this->output_content) {
            return '';
        }

        return strlen($this->output_content) > 200
            ? substr($this->output_content, 0, 200) . '...'
            : $this->output_content;
    }

    /**
     * Calcula el costo aproximado de la generación
     * (Basado en precios de GPT-4o-mini)
     */
    public function getEstimatedCostAttribute(): float
    {
        if (!$this->total_tokens) {
            return 0;
        }

        // Precios aproximados de GPT-4o-mini por 1M tokens
        $inputPrice = 0.15 / 1000000; // $0.15 per 1M input tokens
        $outputPrice = 0.60 / 1000000; // $0.60 per 1M output tokens

        $inputCost = ($this->prompt_tokens ?? 0) * $inputPrice;
        $outputCost = ($this->completion_tokens ?? 0) * $outputPrice;

        return round($inputCost + $outputCost, 6);
    }
}
