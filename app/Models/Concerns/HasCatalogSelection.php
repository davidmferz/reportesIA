<?php

namespace App\Models\Concerns;

use App\Models\Catalog\Branch;
use App\Models\Catalog\DocumentType;
use App\Models\Catalog\Sector;
use App\Models\Catalog\ServiceType;
use App\Models\Catalog\Specialty;
use App\Models\Catalog\Subbranch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Selección del catálogo anidado de proyectos.
 *
 * En el tipo de reporte es el dominio DECLARADO: precarga la pantalla de generación
 * y sirve de baseline para el aviso de dominio. En una generación es el dominio USADO,
 * y ese sí interviene: viaja al prompt como encuadre (CatalogContextService) y sus
 * requisitos de formato se contrastan contra la salida (OutputValidatorService).
 * En el entrenamiento sigue sin intervenir.
 */
trait HasCatalogSelection
{
    /**
     * Precarga los seis niveles. Sin esto, pintar catalogPath() en un listado
     * dispara una consulta por nivel y por fila.
     */
    public function scopeWithCatalog(Builder $query): Builder
    {
        return $query->with([
            'catalogSector',
            'catalogBranch',
            'catalogSubbranch',
            'catalogSpecialty',
            'catalogServiceType',
            'catalogDocumentType',
        ]);
    }

    public function catalogSector(): BelongsTo
    {
        return $this->belongsTo(Sector::class, 'catalog_sector_id');
    }

    public function catalogBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'catalog_branch_id');
    }

    public function catalogSubbranch(): BelongsTo
    {
        return $this->belongsTo(Subbranch::class, 'catalog_subbranch_id');
    }

    public function catalogSpecialty(): BelongsTo
    {
        return $this->belongsTo(Specialty::class, 'catalog_specialty_id');
    }

    public function catalogServiceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class, 'catalog_service_type_id');
    }

    public function catalogDocumentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class, 'catalog_document_type_id');
    }

    /**
     * Ruta legible del catálogo, p. ej.
     * "Primario > Recursos naturales > Pesca > Conservación y explotación pesquera y acuicultura".
     */
    public function catalogPath(): string
    {
        return collect([
            $this->catalogSector?->nombre,
            $this->catalogBranch?->nombre,
            $this->catalogSubbranch?->nombre,
            $this->catalogSpecialty?->nombre,
        ])->filter()->implode(' > ');
    }

    public function catalogServicePath(): string
    {
        return collect([
            $this->catalogServiceType?->nombre,
            $this->catalogDocumentType?->nombre,
        ])->filter()->implode(' > ');
    }
}
