<?php

namespace Tests\Concerns;

use App\Models\LicenseState;
use Illuminate\Support\Carbon;

/**
 * Siembra una licencia vigente para los tests que atraviesan rutas con el
 * middleware `license` activo. Sin esto, con enforcement cableado, toda request
 * protegida redirige a la pantalla de activación.
 */
trait SeedsValidLicense
{
    protected function seedValidLicense(array $overrides = []): LicenseState
    {
        return LicenseState::create(array_merge([
            'raw_token' => 'seed.valid.token',
            'payload' => ['client_id' => 'reportesia', 'kid' => 'k1'],
            'valid_from' => Carbon::now()->subDay(),
            'valid_until' => Carbon::now()->addYear(),
            'max_users' => 999,
            'last_check_at' => Carbon::now()->subMinute(),
            'last_check_result' => 'active',
            'last_seen_at' => Carbon::now()->subMinute(),
            'enforcement_status' => 'valid',
        ], $overrides));
    }
}
