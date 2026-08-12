<?php

namespace App\Services;

/**
 * Rotula cada mensaje `system` del snapshot de prompt para la vista de auditoría.
 *
 * La vista rotulaba "Palabras prohibidas globales" a TODO mensaje con índice > 0,
 * cuando AITrainingService arma hasta seis bloques distintos (maestro, palabras
 * prohibidas, formato, permiso de conocimiento, clasificación e internet). El cliente
 * lo detectó auditando sus propias generaciones: vio tres bloques con contenido
 * completamente diferente y el mismo nombre, y terminó bautizándolos él mismo. El
 * rótulo nunca tocó el runtime —los mensajes se arman por posición, no por nombre—,
 * pero volvía inútil la herramienta con la que se supone que uno diagnostica.
 *
 * El rótulo se deriva del CONTENIDO y no de la posición a propósito: los bloques son
 * condicionales, así que la posición no identifica nada de forma estable, y además así
 * las generaciones YA guardadas quedan bien rotuladas sin migración ni re-generación.
 */
class PromptMessageLabelService
{
    /**
     * Marcador distintivo => rótulo. El orden importa solo si un contenido pudiera
     * matchear dos marcadores; hoy son mutuamente excluyentes.
     */
    private const MARCADORES = [
        'PALABRAS PROHIBIDAS GLOBALES' => 'Palabras prohibidas globales',
        'FORMATO DE SALIDA' => 'Formato de salida',
        'PERMISO DE CONOCIMIENTO DEL MODELO' => 'Permiso de conocimiento del modelo',
        'CLASIFICACIÓN DEL PROYECTO' => 'Clasificación del proyecto',
        'Reglas para la obtención de información' => 'Datos de internet',
    ];

    public static function label(string $content, int $index): string
    {
        // El bloque 0 es siempre el system prompt: Prompt Maestro en modo estándar,
        // system_prompt persistido en modo estricto. Se resuelve por posición porque
        // es el único bloque garantizado y su texto varía según el modo.
        if ($index === 0) {
            return 'Instrucciones del entrenamiento';
        }

        foreach (self::MARCADORES as $marcador => $etiqueta) {
            if (str_contains($content, $marcador)) {
                return $etiqueta;
            }
        }

        // Sin marcador reconocido NO heredamos el rótulo de otro bloque: ese fue el
        // defecto original. Un genérico numerado es menos cómodo pero es honesto.
        return 'Bloque de sistema ' . ($index + 1);
    }
}
