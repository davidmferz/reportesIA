<?php

namespace Tests\Feature\Concerns;

use App\Models\Catalog\Branch;
use App\Models\Catalog\Sector;
use App\Models\Catalog\ServiceType;
use App\Models\Catalog\Specialty;
use App\Models\Catalog\Subbranch;

/**
 * Caminos del catálogo tomados tal cual del Excel, para no inventar datos
 * que la jerarquía real no permitiría.
 */
trait BuildsCatalogPaths
{
    /** Primario > Recursos naturales > Pesca > Conservación y explotación pesquera y acuicultura */
    protected function coherentPath(): array
    {
        $sector = Sector::where('nombre', 'Primario')->firstOrFail();
        $branch = $sector->branches()->where('nombre', 'Recursos naturales')->firstOrFail();
        $subbranch = $branch->subbranches()->where('nombre', 'Pesca')->firstOrFail();
        $specialty = $subbranch->specialties()->firstOrFail();
        $service = ServiceType::where('nombre', 'Calidad de productos y servicios')->firstOrFail();
        $document = $service->documentTypes()->where('nombre', 'Verificación')->firstOrFail();

        return [
            'catalog_sector_id' => $sector->id,
            'catalog_branch_id' => $branch->id,
            'catalog_subbranch_id' => $subbranch->id,
            'catalog_specialty_id' => $specialty->id,
            'catalog_service_type_id' => $service->id,
            'catalog_document_type_id' => $document->id,
        ];
    }

    /** Secundario > Construcción > Inmobiliario > Construcción residencial */
    protected function otherCoherentPath(): array
    {
        $sector = Sector::where('nombre', 'Secundario')->firstOrFail();
        $branch = $sector->branches()->where('nombre', 'Construcción')->firstOrFail();
        $subbranch = $branch->subbranches()->where('nombre', 'Inmobiliario')->firstOrFail();
        $specialty = $subbranch->specialties()->where('nombre', 'Construcción residencial')->firstOrFail();
        $service = ServiceType::where('nombre', 'Gestión ambiental')->firstOrFail();
        $document = $service->documentTypes()->where('nombre', 'Plan')->firstOrFail();

        return [
            'catalog_sector_id' => $sector->id,
            'catalog_branch_id' => $branch->id,
            'catalog_subbranch_id' => $subbranch->id,
            'catalog_specialty_id' => $specialty->id,
            'catalog_service_type_id' => $service->id,
            'catalog_document_type_id' => $document->id,
        ];
    }

    /** Rama real, pero de OTRO sector que el enviado. */
    protected function foreignBranchId(): int
    {
        return Branch::where('nombre', 'Construcción')->firstOrFail()->id;
    }

    /** Subrama real, pero de OTRA rama. */
    protected function foreignSubbranchId(): int
    {
        return Subbranch::where('nombre', 'Inmobiliario')->firstOrFail()->id;
    }

    /** Especialidad real, pero de OTRA subrama. */
    protected function foreignSpecialtyId(): int
    {
        return Specialty::where('nombre', 'Construcción residencial')->firstOrFail()->id;
    }

    /** Tipo de documento real, pero de OTRO tipo de servicio. */
    protected function foreignDocumentTypeId(): int
    {
        return ServiceType::where('nombre', 'Información y análisis estadístico')
            ->firstOrFail()
            ->documentTypes()
            ->where('nombre', 'Informe')
            ->firstOrFail()
            ->id;
    }
}
