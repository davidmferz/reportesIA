<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('report_types', function (Blueprint $table) {
            // Cuando está activo, el modelo puede aportar su conocimiento experto del
            // dominio para enriquecer el documento (además de los datos del cliente).
            // Default apagado: mantiene la fidelidad "solo datos del cliente".
            $table->boolean('usa_conocimiento_modelo')->default(false)->after('usa_internet');
        });
    }

    public function down(): void
    {
        Schema::table('report_types', function (Blueprint $table) {
            $table->dropColumn('usa_conocimiento_modelo');
        });
    }
};
