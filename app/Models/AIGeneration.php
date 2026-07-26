<?php

namespace App\Models;

use App\Models\Concerns\HasCatalogSelection;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AIGeneration extends Model
{
    use HasCatalogSelection;
    use LogsActivity;

    /**
     * El contenido completo ya vive en esta misma tabla (input_content, output_content,
     * prompt_messages, validation_result). En el log de actividad guardamos solo el
     * "qué pasó" (estado, tokens, quién, cuándo) sin duplicar los blobs.
     */
    public array $activityLogExclude = [
        'input_content',
        'output_content',
        'prompt_messages',
        'validation_result',
    ];

    protected $table = 'ai_generations';

    protected $fillable = [
        'ai_training_id',
        'user_id',
        'chapter_id',
        'catalog_sector_id',
        'catalog_branch_id',
        'catalog_subbranch_id',
        'catalog_specialty_id',
        'catalog_service_type_id',
        'catalog_document_type_id',
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
     * Contenido de salida listo para mostrar o descargar: la IA no puede producir
     * imágenes reales, así que cualquier referencia a imagen (Markdown o <img>)
     * apunta a un archivo inexistente. Se reemplaza por un placeholder legible
     * tanto en el visor web (renderizado como Markdown) como en el .md descargado.
     */
    public function getDisplayOutputAttribute(): string
    {
        $content = $this->output_content;

        if (!$content) {
            return '';
        }

        $placeholder = function (string $alt, bool $standalone): string {
            $alt = trim($alt);

            if ($standalone) {
                return $alt !== ''
                    ? "> 📷 **Figura:** {$alt}"
                    : '> 📷 **Figura referenciada en el documento original**';
            }

            return $alt !== ''
                ? "📷 *[Figura: {$alt}]*"
                : '📷 *[Figura referenciada]*';
        };

        $markdownImage = '!\[([^\]]*)\]\([^)]*\)';
        $htmlImage = '<img\b[^>]*\/?>';

        // Imágenes que ocupan una línea completa → placeholder en bloque (blockquote)
        $content = preg_replace_callback(
            '/^[ \t]*' . $markdownImage . '[ \t]*$/mu',
            fn (array $m) => $placeholder($m[1], true),
            $content
        );

        $content = preg_replace_callback(
            '/^[ \t]*' . $htmlImage . '[ \t]*$/miu',
            function (array $m) use ($placeholder) {
                preg_match('/\balt\s*=\s*["\']([^"\']*)["\']/iu', $m[0], $alt);

                return $placeholder($alt[1] ?? '', true);
            },
            $content
        );

        // Imágenes dentro de un párrafo → placeholder en línea
        $content = preg_replace_callback(
            '/' . $markdownImage . '/u',
            fn (array $m) => $placeholder($m[1], false),
            $content
        );

        $content = preg_replace_callback(
            '/' . $htmlImage . '/iu',
            function (array $m) use ($placeholder) {
                preg_match('/\balt\s*=\s*["\']([^"\']*)["\']/iu', $m[0], $alt);

                return $placeholder($alt[1] ?? '', false);
            },
            $content
        );

        return $this->convertTabTablesToMarkdown($content);
    }

    /**
     * La IA emite tablas con columnas separadas por tabuladores (copiadas del Word
     * original), que GFM no reconoce y renderiza como párrafos aplanados. Convierte
     * bloques de 2+ líneas consecutivas con tabs en tablas Markdown con pipes,
     * usando la primera línea como encabezado.
     */
    private function convertTabTablesToMarkdown(string $content): string
    {
        $lines = explode("\n", $content);
        $out = [];
        $block = [];

        $flush = function () use (&$block, &$out) {
            if (count($block) < 2) {
                array_push($out, ...$block);
                $block = [];

                return;
            }

            $rows = array_map(
                fn (string $line) => array_map(
                    fn (string $cell) => str_replace('|', '\\|', trim($cell)),
                    explode("\t", $line)
                ),
                $block
            );

            $columns = max(array_map('count', $rows));
            $rows = array_map(
                fn (array $row) => array_pad($row, $columns, ''),
                $rows
            );

            // GFM no permite que una tabla interrumpa un párrafo: separar del texto previo
            if ($out !== [] && trim(end($out)) !== '') {
                $out[] = '';
            }

            $toRow = fn (array $cells) => '| ' . implode(' | ', $cells) . ' |';

            $out[] = $toRow(array_shift($rows));
            $out[] = $toRow(array_fill(0, $columns, '---'));

            foreach ($rows as $row) {
                $out[] = $toRow($row);
            }

            $block = [];
        };

        foreach ($lines as $line) {
            if (str_contains($line, "\t") && trim($line) !== '') {
                $block[] = $line;
                continue;
            }

            $flush();
            $out[] = $line;
        }

        $flush();

        return implode("\n", $out);
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
