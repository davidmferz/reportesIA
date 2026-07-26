<?php

namespace App\Services;

use App\Models\Catalog\Sector;
use App\Models\Catalog\ServiceType;
use Illuminate\Validation\Rule;

/**
 * Catálogo anidado de clasificación de proyectos.
 *
 * Sirve el árbol completo a las pantallas (la cascada se resuelve en el cliente:
 * son ~180 nodos, no justifica un round-trip por nivel) y construye las reglas de
 * validación que impiden guardar un hijo que no cuelga del padre enviado.
 */
class CatalogService
{
    /**
     * Columnas donde se persiste la selección, de padre a hijo.
     *
     * @return list<string>
     */
    public static function columns(): array
    {
        return [
            'catalog_sector_id',
            'catalog_branch_id',
            'catalog_subbranch_id',
            'catalog_specialty_id',
            'catalog_service_type_id',
            'catalog_document_type_id',
        ];
    }

    /**
     * Cada hijo con la columna que lo ata a su padre.
     *
     * @return array<string, array{parent: string, table: string, foreign: string}>
     */
    private static function nesting(): array
    {
        return [
            'catalog_branch_id' => [
                'parent' => 'catalog_sector_id',
                'table' => 'catalog_branches',
                'foreign' => 'catalog_sector_id',
            ],
            'catalog_subbranch_id' => [
                'parent' => 'catalog_branch_id',
                'table' => 'catalog_subbranches',
                'foreign' => 'catalog_branch_id',
            ],
            'catalog_specialty_id' => [
                'parent' => 'catalog_subbranch_id',
                'table' => 'catalog_specialties',
                'foreign' => 'catalog_subbranch_id',
            ],
            'catalog_document_type_id' => [
                'parent' => 'catalog_service_type_id',
                'table' => 'catalog_document_types',
                'foreign' => 'catalog_service_type_id',
            ],
        ];
    }

    /**
     * Árbol completo, listo para serializar a JSON en la vista.
     */
    public function tree(): array
    {
        $sectors = Sector::with('branches.subbranches.specialties')
            ->orderBy('nombre')
            ->get()
            ->map(fn (Sector $sector) => [
                'id' => $sector->id,
                'nombre' => $sector->nombre,
                'branches' => $sector->branches->map(fn ($branch) => [
                    'id' => $branch->id,
                    'nombre' => $branch->nombre,
                    'subbranches' => $branch->subbranches->map(fn ($subbranch) => [
                        'id' => $subbranch->id,
                        'nombre' => $subbranch->nombre,
                        'specialties' => $subbranch->specialties->map(fn ($specialty) => [
                            'id' => $specialty->id,
                            'nombre' => $specialty->nombre,
                        ])->values()->all(),
                    ])->values()->all(),
                ])->values()->all(),
            ])->values()->all();

        $serviceTypes = ServiceType::with('documentTypes')
            ->orderBy('nombre')
            ->get()
            ->map(fn (ServiceType $serviceType) => [
                'id' => $serviceType->id,
                'nombre' => $serviceType->nombre,
                'document_types' => $serviceType->documentTypes->map(fn ($documentType) => [
                    'id' => $documentType->id,
                    'nombre' => $documentType->nombre,
                ])->values()->all(),
            ])->values()->all();

        return [
            'sectors' => $sectors,
            'service_types' => $serviceTypes,
        ];
    }

    /**
     * Reglas de validación para la selección.
     *
     * La selección completa es opcional, pero un hijo solo se acepta si existe
     * bajo el padre que viene en la misma petición. Si el padre viene vacío,
     * `Rule::exists()->where(col, null)` se traduce a `WHERE col IS NULL` y ningún
     * registro califica: eso rechaza los hijos huérfanos sin lógica extra.
     */
    public function validationRules(array $input): array
    {
        $rules = [
            'catalog_sector_id' => ['nullable', 'integer', Rule::exists('catalog_sectors', 'id')],
            'catalog_service_type_id' => ['nullable', 'integer', Rule::exists('catalog_service_types', 'id')],
        ];

        foreach (self::nesting() as $column => $nesting) {
            $rules[$column] = [
                'nullable',
                'integer',
                Rule::exists($nesting['table'], 'id')
                    ->where($nesting['foreign'], $input[$nesting['parent']] ?? null),
            ];
        }

        return $rules;
    }

    public function validationMessages(): array
    {
        return [
            'catalog_sector_id.exists' => 'El sector seleccionado no existe.',
            'catalog_branch_id.exists' => 'La rama seleccionada no pertenece al sector elegido.',
            'catalog_subbranch_id.exists' => 'La subrama seleccionada no pertenece a la rama elegida.',
            'catalog_specialty_id.exists' => 'La especialidad seleccionada no pertenece a la subrama elegida.',
            'catalog_service_type_id.exists' => 'El tipo de servicio seleccionado no existe.',
            'catalog_document_type_id.exists' => 'El tipo de documento seleccionado no pertenece al tipo de servicio elegido.',
        ];
    }

    /**
     * Extrae las seis columnas de la selección, con null cuando no vinieron.
     */
    public function selectionFrom(array $input): array
    {
        $selection = [];

        foreach (self::columns() as $column) {
            $selection[$column] = $input[$column] ?? null;
        }

        return $selection;
    }
}
