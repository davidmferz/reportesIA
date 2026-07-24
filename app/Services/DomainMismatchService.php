<?php

namespace App\Services;

use App\Models\Catalog\Branch;
use App\Models\Catalog\Sector;
use App\Models\Catalog\Specialty;
use App\Models\Catalog\Subbranch;

/**
 * Aviso de dominio: detecta cuándo se genera con una clasificación de dominio
 * distinta a la que declara el tipo de reporte.
 *
 * Existe por la causa raíz documentada de las "alucinaciones": un tipo de reporte
 * entrenado sobre un dominio y usado para otro. El aviso NUNCA bloquea —hay casos
 * legítimos— y no interviene en el prompt ni en el entrenamiento.
 *
 * Compara solo los cuatro niveles de dominio. El tipo de servicio y el de documento
 * quedan fuera a propósito: cambiar de entregable no es cambiar de dominio.
 */
class DomainMismatchService
{
    /**
     * Niveles de dominio de mayor a menor, con su etiqueta y qué tan grave es
     * el salto: cambiar de sector o de rama es otro negocio; cambiar de
     * especialidad dentro de la misma subrama es un matiz.
     */
    private const NIVELES = [
        'catalog_sector_id' => ['etiqueta' => 'Sector', 'severidad' => 'alta', 'modelo' => Sector::class],
        'catalog_branch_id' => ['etiqueta' => 'Rama', 'severidad' => 'alta', 'modelo' => Branch::class],
        'catalog_subbranch_id' => ['etiqueta' => 'Subrama', 'severidad' => 'media', 'modelo' => Subbranch::class],
        'catalog_specialty_id' => ['etiqueta' => 'Especialidad', 'severidad' => 'baja', 'modelo' => Specialty::class],
    ];

    /**
     * Primera divergencia de dominio, de arriba hacia abajo.
     *
     * Devuelve null cuando no hay nada que avisar: dominios iguales, o alguno de
     * los dos lados sin clasificar en ese nivel (eso es dato faltante, no un
     * cambio de dominio).
     *
     * @param  array<string, int|null>  $declarado  Dominio del tipo de reporte.
     * @param  array<string, int|null>  $usado      Dominio elegido al generar.
     * @return array{nivel: string, etiqueta: string, severidad: string, declarado: string, usado: string}|null
     */
    public function between(array $declarado, array $usado): ?array
    {
        foreach (self::NIVELES as $nivel => $meta) {
            $a = $declarado[$nivel] ?? null;
            $b = $usado[$nivel] ?? null;

            if ($a === null || $b === null) {
                // Sin ambos lados no hay comparación posible; tampoco tiene sentido
                // seguir bajando, porque los niveles inferiores cuelgan de este.
                return null;
            }

            if ((int) $a === (int) $b) {
                continue;
            }

            return [
                'nivel' => $nivel,
                'etiqueta' => $meta['etiqueta'],
                'severidad' => $meta['severidad'],
                'declarado' => $this->nombre($meta['modelo'], $a),
                'usado' => $this->nombre($meta['modelo'], $b),
            ];
        }

        return null;
    }

    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $modelo
     */
    private function nombre(string $modelo, int|string $id): string
    {
        return $modelo::find($id)?->nombre ?? '—';
    }
}
