<?php

namespace App\Services;

class PromptParserService
{
    /**
     * Extrae palabras y frases prohibidas del prompt del cliente.
     * Reconoce secciones tipo "PALABRAS PROHIBIDAS:", "PROHIBIDO:", "No usar:", etc.
     */
    public function extractForbiddenTerms(?string $prompt): array
    {
        if (empty($prompt)) {
            return [];
        }

        $terms = [];
        $patterns = [
            '/PALABRAS\s+PROHIBIDAS\s*:?\s*\n?(.+?)(?=\n\s*\n|\n[A-ZÁÉÍÓÚÑ]{3,}|\z)/sui',
            '/PROHIBIDO\s*:?\s*\n?(.+?)(?=\n\s*\n|\n[A-ZÁÉÍÓÚÑ]{3,}|\z)/sui',
            '/NO\s+USAR\s*:?\s*\n?(.+?)(?=\n\s*\n|\n[A-ZÁÉÍÓÚÑ]{3,}|\z)/sui',
            '/RESTRICCIONES\s*\(?OBLIGATORIAS?\)?\s*:?\s*\n?(.+?)(?=\n\s*\n|\n[A-ZÁÉÍÓÚÑ]{3,}|\z)/sui',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $prompt, $matches)) {
                foreach ($matches[1] as $block) {
                    $terms = array_merge($terms, $this->splitTerms($block));
                }
            }
        }

        // También capturar términos entre comillas precedidos por "no usar", "evitar", etc.
        if (preg_match_all('/(?:no\s+usar|evitar|prohibido|sin\s+usar)[^"\'\n]{0,40}["\']([^"\']{2,40})["\']/ui', $prompt, $m)) {
            foreach ($m[1] as $term) {
                $terms[] = trim($term);
            }
        }

        $terms = array_map(fn($t) => mb_strtolower(trim($t), 'UTF-8'), $terms);
        $terms = array_filter($terms, fn($t) => mb_strlen($t) >= 3 && mb_strlen($t) <= 60);

        return array_values(array_unique($terms));
    }

    /**
     * Detecta el límite de palabras del prompt si está especificado.
     * Retorna ['min' => int|null, 'max' => int|null].
     */
    public function extractWordLimits(?string $prompt): array
    {
        $limits = ['min' => null, 'max' => null];
        if (empty($prompt)) {
            return $limits;
        }

        if (preg_match('/(?:m[áa]ximo|m[áa]x|no\s+mayor\s+a|no\s+m[áa]s\s+de)\s*(?:de\s*)?(\d{2,5})\s*palabras/ui', $prompt, $m)) {
            $limits['max'] = (int) $m[1];
        }
        if (preg_match('/(?:m[íi]nimo|m[íi]n|al\s+menos)\s*(?:de\s*)?(\d{2,5})\s*palabras/ui', $prompt, $m)) {
            $limits['min'] = (int) $m[1];
        }
        // "más de N palabras" / "mayor a/que N palabras" → mínimo. El lookbehind
        // (?<!no\s) evita capturar "no más de N" / "no mayor a N" — esos son máximos
        // y los captura el primer regex.
        if (preg_match('/(?<!no\s)(?:m[áa]s\s+(?:de|que)|mayor\s+(?:a|que))\s+(\d{2,5})\s*palabras/ui', $prompt, $m)) {
            $limits['min'] = (int) $m[1];
        }
        if (preg_match('/(\d{2,5})\s*(?:a|-|hasta)\s*(\d{2,5})\s*palabras/ui', $prompt, $m)) {
            $limits['min'] = (int) $m[1];
            $limits['max'] = (int) $m[2];
        }
        if (preg_match('/contenido\s+(?:m[íi]nimo\s+y\s+m[áa]ximo|exacto)\s+(?:es\s+)?de\s+(\d{2,5})\s*palabras/ui', $prompt, $m)) {
            $limits['max'] = (int) $m[1];
            $limits['min'] = $limits['min'] ?? (int) $m[1];
        }

        return $limits;
    }

    private function splitTerms(string $block): array
    {
        // Limpiar: quitar bullets, comillas, paréntesis con explicaciones
        $block = preg_replace('/\([^)]{0,80}\)/', '', $block);
        $block = str_replace(['"', "'", '“', '”', '‘', '’', '•', '-', '*'], ' ', $block);

        // Separar por coma, salto de línea, punto y coma
        $parts = preg_split('/[,;\n]+/', $block);

        return array_map('trim', array_filter($parts, fn($p) => trim($p) !== ''));
    }
}
