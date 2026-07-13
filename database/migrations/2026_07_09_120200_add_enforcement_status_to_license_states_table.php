<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('license_states', function (Blueprint $table) {
            // Último estado de enforcement resuelto: sirve para auditar
            // transiciones (grace→blocked) una sola vez, no por request.
            $table->string('enforcement_status')->nullable()->after('last_check_result');
        });
    }

    public function down(): void
    {
        Schema::table('license_states', function (Blueprint $table) {
            $table->dropColumn('enforcement_status');
        });
    }
};
