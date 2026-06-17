<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('report_types', function (Blueprint $table) {
            // Cuando está activo, la generación enriquece el documento con datos
            // obtenidos de internet (web_search nativo de OpenAI). Default apagado:
            // los tipos existentes mantienen la fidelidad "solo datos del cliente".
            $table->boolean('usa_internet')->default(false)->after('modo_estricto');
        });
    }

    public function down(): void
    {
        Schema::table('report_types', function (Blueprint $table) {
            $table->dropColumn('usa_internet');
        });
    }
};
