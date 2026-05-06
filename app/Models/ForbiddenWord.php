<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ForbiddenWord extends Model
{
    protected $fillable = ['word', 'reason', 'active'];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    /**
     * Lista normalizada (lowercase, trimmed) de palabras prohibidas activas.
     * La consume el validador, el saneador y la inyección al system prompt.
     */
    public static function activeWords(): array
    {
        return static::active()
            ->pluck('word')
            ->map(fn ($w) => mb_strtolower(trim((string) $w), 'UTF-8'))
            ->filter(fn ($w) => $w !== '' && mb_strlen($w) >= 2 && mb_strlen($w) <= 100)
            ->unique()
            ->values()
            ->toArray();
    }
}
