<?php

namespace Database\Seeders;

use App\Models\Catalog\Sector;
use App\Models\Catalog\ServiceType;
use Illuminate\Database\Seeder;

/**
 * Carga el catálogo de clasificación de proyectos.
 *
 * Fuente: database/data/catalogo_proyectos.php, extraído de
 * docs/Generador_Proyectos_Anidado_Excel_2019.xlsx.
 *
 * Idempotente: se puede re-ejecutar sin duplicar ni perder las selecciones ya guardadas.
 */
class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = require database_path('data/catalogo_proyectos.php');

        foreach ($catalog['hierarchy'] as $sectorName => $branches) {
            $sector = Sector::firstOrCreate(['nombre' => $sectorName]);

            foreach ($branches as $branchName => $subbranches) {
                $branch = $sector->branches()->firstOrCreate(['nombre' => $branchName]);

                foreach ($subbranches as $subbranchName => $specialties) {
                    $subbranch = $branch->subbranches()->firstOrCreate(['nombre' => $subbranchName]);

                    foreach ($specialties as $specialtyName) {
                        $subbranch->specialties()->firstOrCreate(['nombre' => $specialtyName]);
                    }
                }
            }
        }

        foreach ($catalog['services'] as $serviceName => $documentTypes) {
            $serviceType = ServiceType::firstOrCreate(['nombre' => $serviceName]);

            foreach ($documentTypes as $documentTypeName) {
                $serviceType->documentTypes()->firstOrCreate(['nombre' => $documentTypeName]);
            }
        }
    }
}
