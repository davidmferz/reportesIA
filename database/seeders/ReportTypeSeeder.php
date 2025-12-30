<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ReportType;
use App\Models\User;

class ReportTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener el primer usuario admin para los campos de auditoría
        $admin = User::where('is_admin', true)->first();

        $reportTypes = [
            'Reporte Mensual de Ventas',
            'Reporte de Inventario',
            'Reporte de Producción',
            'Reporte de Calidad',
            'Reporte de Mantenimiento',
        ];

        foreach ($reportTypes as $nombre) {
            ReportType::create([
                'nombre' => $nombre,
                'created_by' => $admin ? $admin->id : null,
            ]);
        }
    }
}
