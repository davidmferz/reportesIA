<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

/**
 * Investiga un tema en internet usando la herramienta web_search nativa de OpenAI
 * (Responses API) y devuelve un brief con datos verificables + las fuentes citadas.
 *
 * Diseño: es ADITIVO y FAIL-SAFE. Ante cualquier error (modelo sin soporte de
 * web_search, sin saldo, timeout, etc.) NUNCA lanza: devuelve used=false para que
 * la generación continúe normalmente, solo que sin datos externos. Así, prender el
 * toggle de internet jamás puede romper una generación.
 */
class WebResearchService
{
    /**
     * @return array{used: bool, brief: string, sources: array<int, string>}
     */
    public function research(string $topic, ?string $model = null): array
    {
        $empty = ['used' => false, 'brief' => '', 'sources' => []];

        $topic = trim($topic);
        if ($topic === '') {
            return $empty;
        }

        // El tema puede ser un documento largo: acotamos para no inflar el costo de
        // la búsqueda (con el inicio del input alcanza para que el modelo entienda
        // de qué buscar).
        if (mb_strlen($topic) > 4000) {
            $topic = mb_substr($topic, 0, 4000);
        }

        $model = $model ?: config('openai.search_model', 'gpt-4o-mini');
        $toolType = config('openai.web_search_tool', 'web_search');

        try {
            $response = OpenAI::responses()->create([
                'model' => $model,
                'tools' => [['type' => $toolType]],
                'instructions' => 'Sos un asistente de investigación técnica. Seguí esta metodología: '
                    . '1) Determiná exactamente qué dato falta en la documentación aportada (el tema '
                    . 'indicado). 2) Buscá en internet ÚNICAMENTE ese dato, en fuentes públicas fiables '
                    . 'y ACTUALES. 3) No incluyas información irrelevante al dato buscado. 4) No busques '
                    . 'ni repitas lo que la documentación ya contiene. 5) Si no existe información '
                    . 'pública suficiente para un dato, decilo explícitamente en vez de inventarlo. '
                    . 'Devolvé un resumen técnico en español (máximo ~400 palabras) con datos concretos: '
                    . 'cifras, normativas, referencias y buenas prácticas. Terminá SIEMPRE con una '
                    . "sección 'FUENTES:' listando las URLs consultadas, una por línea.",
                'input' => "Tema a investigar:\n\n{$topic}",
            ]);

            $text = trim($response->outputText ?? '');
            if ($text === '') {
                return $empty;
            }

            return [
                'used' => true,
                'brief' => $text,
                'sources' => $this->extractSources($text),
            ];
        } catch (\Throwable $e) {
            Log::warning('WebResearchService falló; la generación continúa sin datos de internet: ' . $e->getMessage());
            return $empty;
        }
    }

    /**
     * Extrae las URLs citadas del brief (sección FUENTES y cualquier URL suelta).
     *
     * @return array<int, string>
     */
    private function extractSources(string $text): array
    {
        preg_match_all('#https?://[^\s)\]<>"]+#i', $text, $matches);

        return array_values(array_unique($matches[0] ?? []));
    }
}
