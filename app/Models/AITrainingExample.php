<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AITrainingExample extends Model
{
    protected $table = 'ai_training_examples';

    protected $fillable = [
        'ai_training_id',
        'grupo_id',
        'chapter_id',
        'capitulo',
        'input_content',
        'output_content',
        'input_files_count',
        'processed_at',
        'audit_status',
        'audit_findings',
        'audited_at',
        'excluido_few_shot',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
        'audited_at' => 'datetime',
        'input_files_count' => 'integer',
        'audit_findings' => 'array',
        'excluido_few_shot' => 'boolean',
    ];

    /**
     * Capítulo del ejemplo. Es lo que permite emparejarlo con el capítulo que se
     * pide al generar; `capitulo` queda como etiqueta legible para el few-shot.
     */
    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    /**
     * Relación con el entrenamiento
     */
    public function training(): BelongsTo
    {
        return $this->belongsTo(AITraining::class, 'ai_training_id');
    }

    /**
     * Obtiene los archivos originales asociados a este grupo
     */
    public function originalFiles()
    {
        return ReportTypeFile::where('grupo_id', $this->grupo_id)->get();
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
        return strlen($this->output_content) > 200
            ? substr($this->output_content, 0, 200) . '...'
            : $this->output_content;
    }

    /**
     * Calcula el tamaño total del contenido en caracteres
     */
    public function getTotalSizeAttribute(): int
    {
        return strlen($this->input_content) + strlen($this->output_content);
    }

    /**
     * Formatea el tamaño para mostrar
     */
    public function getFormattedSizeAttribute(): string
    {
        $size = $this->total_size;

        if ($size >= 1000000) {
            return number_format($size / 1000000, 2) . ' MB';
        } elseif ($size >= 1000) {
            return number_format($size / 1000, 2) . ' KB';
        }

        return $size . ' bytes';
    }
}
